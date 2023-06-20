<?php
/**
 * Gravity Perks // Nested Forms // Add Child Entry on Render
 * https://gravitywiz.com/documentation/gravity-forms-nested-forms/
 *
 * Programattically create and attach a child entry from ACF repeater to a Nested Form field when the parent form is rendered.
 */
// Update "10" to your form ID and "24" to your Nested Form field ID.
add_filter( 'gpnf_submitted_entry_ids_10_24', function( $entry_ids, $parent_form, $nested_form_field ) {

	$hash = gpnf_session_hash( $parent_form['id'] );
	$session = new GPNF_Session( $parent_form['id'] );
	
	// Get current entry IDs from the session
	$currentEntryIds = $session->get( 'nested_entries' );
	$currentIds = array();
	$currentPartNums = array();
	
	if ( ! empty( $currentEntryIds[ $nested_form_field->id ] ) ) {
		$currentIds = $currentEntryIds[ $nested_form_field->id ];
	}
	
	//loop through current session entries
	foreach($currentIds as $partsListId){
		intval($partsListId);
		if(GFAPI::entry_exists( $partsListId )){
			$partsToRegisterEntry = GFAPI::get_entry($partsListId);
			
			//build array of part numbers already added to the session
			$currentPartNums[] = $partsToRegisterEntry[1];
		};
	}
	
	$post_id = get_the_ID();
	
	//ACF repeater field
	if( have_rows('claim_parts_list', $post_id) ){
		
		while( have_rows('claim_parts_list', $post_id) ){
			
			the_row();
			$partNum = get_sub_field('claim_part_number');
			$partDesc = get_sub_field('claim_part_description');
			$partCost = get_sub_field('claim_part_cost');
			
			//build array of new child entries to be added to the session
			$childEntryParts[] = array('1' => $partNum, '5' => $partDesc, '30' => $partCost);
			
		}

	}
	
	foreach($childEntryParts as $childEntryPart){
		
		//Add child entry only if the part number hasn't been added as a child entry yet
		if(!in_array($childEntryPart['1'], $currentPartNums, true)){
			
			$childEntry = gpnf_add_child_entry( $hash, $nested_form_field->id, $childEntryPart, $parent_form['id'] );
			//Attach new child entry to the session.
			$session->add_child_entry( $childEntry );
			
		}
		
	}

	// Get all entry IDs from the session and return them.
	$session_entry_ids = $session->get( 'nested_entries' );
	if ( ! empty( $session_entry_ids[ $nested_form_field->id ] ) ) {
		$entry_ids = $session_entry_ids[ $nested_form_field->id ];
	}
	
	return $entry_ids;
}, 10, 3 );

if ( ! function_exists( 'gpnf_session_hash' ) ) {
	function gpnf_session_hash( $form_id ) {
		$session = new GPNF_Session( $form_id );
		return $session->get_runtime_hashcode();
	}
}

if ( ! function_exists( 'gpnf_add_child_entry' ) ) {
	/**
	 * @param int   $parent_entry_id      The ID of the entry to which this child entry should be attached.
	 * @param int   $nested_form_field_id The ID of the Nested Form field on the parent form to which this child entry should be attached.
	 * @param array $field_values         An array of field values that will be used to created the child entry (e.g. array( 1 => 'value' )).
	 * @param int   $parent_form_id       The ID of the parent entry's form. If not provided, will be looked up based on the provided parent entry ID.
	 */
	function gpnf_add_child_entry( $parent_entry_id, $nested_form_field_id, $field_values = array(), $parent_form_id = false ) {

		if ( ! $parent_form_id ) {
			$parent_entry   = GFAPI::get_entry( $parent_entry_id );
			$parent_form_id = $parent_entry['form_id'];
		}

		$nested_form_field = GFAPI::get_field( $parent_form_id, $nested_form_field_id );

		$new_child_entry = array_replace( array(
			// The ID of the parent form.
			'form_id'                               => $nested_form_field->gpnfForm,
			'created_by'                            => null,
			// The ID of the parent entry.
			GPNF_Entry::ENTRY_PARENT_KEY            => $parent_entry_id,
			// The ID of the parent form.
			GPNF_Entry::ENTRY_PARENT_FORM_KEY       => $parent_form_id,
			// The ID of the Nested Form field on the parent form.
			GPNF_Entry::ENTRY_NESTED_FORM_FIELD_KEY => $nested_form_field_id,
		), $field_values );

		$new_child_entry_id = GFAPI::add_entry( $new_child_entry );

		return $new_child_entry_id;
	}
}
