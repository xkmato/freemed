<?php
 // $Id$
 // desc: module prototype
 // lic : GPL, v2

LoadObjectDependency('_FreeMED.BaseModule');

// Class: FreeMED.MaintenanceModule
//
//	Database table maintenance module superclass. This is descended
//	from <BaseModule>.
//
class MaintenanceModule extends BaseModule {

	// override variables
	var $CATEGORY_NAME = "Support Data";
	var $CATEGORY_VERSION = "0.2.1";

	// Variable: $this->order_field
	//
	//	Defines the ORDER BY clause for built-in functions. This
	//	should be present in all child modules. It is set to 'id'
	//	by default, which is horrible behavior, and should be
	//	overridden in all child classes.
	//
	var $order_field = 'id';

	var $form_vars;

	// Variable: $this->table_name
	//
	//	Defines the name of the SQL table used and/or defined
	//	by this module. Must be defined for the module to
	//	function properly if a table definition is used.
	//
	// Example:
	//
	//	$this->table_name = 'facility';
	//
	var $table_name;

	// Variable: $this->widget_hash
	//
	//	Specifies the format of the <widget> method. This is
	//	formatted by having SQL field names surrounded by '##'s.
	//
	// Example:
	//
	//	$this->widget_hash = '##phylname##, ##phyfname##';
	//	
	var $widget_hash;

	// Variable: $this->rpc_field_map
	//
	//	Specifies the format of the XML-RPC structures returned by
	//	the FreeMED.DynamicModule.picklist method. These are passed
	//	as key => value, where key is the target name of the
	//	structure item and value is the name of the SQL field. "id"
	//	is passed as "id" by default. If this array is not
	//	defined, FreeMED.DynamicModule.picklist will fail for the
	//	target module.
	//
	// Example:
	//
	//	$this->rpc_field_map = array ( 'last_name' => 'ptlname' );
	//
	var $rpc_field_map;

	// contructor method
	function MaintenanceModule () {
		// Set reference for itemlist to be parent menu
		$GLOBALS['_ref'] = 'db_maintenance.php';

		// Store the rpc map in the meta information
		$this->_SetMetaInformation('rpc_field_map', $this->rpc_field_map);

		// Call parent constructor
		$this->BaseModule();
	} // end function MaintenanceModule

	// override check_vars method
	function check_vars ($nullvar = "") {
		global $module;
		if (!isset($module)) 
		{
			trigger_error("Module not Defined", E_ERROR);
		}
		return true;
	} // end function check_vars

	// function main
	// - generic main function
	function main ($nullvar = "") {
		global $display_buffer;
		global $action, $__submit;

		// Handle a "Cancel" button being pushed
		if ($__submit==__("Cancel")) {
			$action = "";
			$this->view();
			return NULL;
		}

		switch ($action) {
			case "add":
				$this->add();
				break;

			case "addform":
				$this->addform();
				break;

			case "del":
			case "delete":
				$this->del();
				break;

			case "mod":
			case "modify":
				$this->mod();
				break;

			case "modform":
				global $id;
				if (empty($id) or ($id<1)) {
					template_display();
				}
				$this->modform();
				break;

			case "view":
			default:
				$action = "";
				$this->view();
				break;
		} // end switch action
	} // end function main

	// function display_message
	function display_message () {
		global $display_buffer;
		// if there's a message, display it
		if (isset($this->message)) {
			$display_buffer .= "
			<p/>
			<div ALIGN=\"CENTER\">
			<b>".prepare($this->message)."</b>
			</div>
			";
		}
	} // end display message

	// ********************** MODULE SPECIFIC ACTIONS *********************

	// Method: _add
	//
	//	Basic superclass addition routine.
	//
	// Parameters:
	//
	//	$_param - (optional) Associative array of values. If
	//	specified, _add will run quiet. The associative array
	//	is in the format of sql_name => sql_value.
	//
	// Returns:
	//
	//	Nothing if there are no parameters. If $_param is
	//	specified, _add will return the id number if successful
	//	or false if unsuccessful.
	//
	// See Also:
	//	<add>
	//
	function _add ($_param = NULL) {
		//print "param = "; print_r($_param); print "\n";
		global $display_buffer, $action;
		foreach ($GLOBALS AS $k => $v) { global ${$k}; }

		// If there are parameters, import them into the global
		// scope, then set their values
		if (is_array($_param)) {
			foreach ($_param AS $k => $v) {
				global ${$k};
				${$k} = $v;
				//print "mapped $k to $v\n";
			}
		}

		$result = $GLOBALS['sql']->query (
			$GLOBALS['sql']->insert_query (
				$this->table_name,
				$this->variables
			)
		);

		if ($result) {
			$this->message = __("Record added successfully.");
			if (is_array($_param)) { return $GLOBALS['sql']->last_record($result); }
		} else {
			$this->message = __("Record addition failed.");
			if (is_array($_param)) { return false; }
		}
		$action = "";
		$this->view(); $this->display_message();
	} // end function _add

	// Method: add
	//
	//	Wrapper for _add. This exists so that FreeMED modules
	//	can override the basic add functionality while still
	//	having access to the low-level functionality.
	//
	// See Also:
	//	<_add>
	//
	function add () { $this->_add(); }

	// Method: _del
	//
	//	Basic superclass deletion routine.
	//
	// Parameters:
	//
	//	$_param - (optional) Id number for the record to
	//	be deleted. 
	//
	// Returns:
	//
	//	Nothing if there are no parameters. If $_param is
	//	specified, _del will return boolean true or false
	//	depending on whether it is successful.
	//
	// See Also:
	//	<del>
	//
	function _del ($_id = -1) {
		global $display_buffer;
		global $id, $module, $action;

		// Override with parameter, if present
		if ($_id > 0) { $id = $_id; }

		$query = "DELETE FROM $this->table_name ".
			"WHERE id = '".prepare($id)."'";
		$result = $GLOBALS['sql']->query ($query);

		// If we were passed a parameter, we don't go to doing
		// anything fancy, just return a true or false.
		if ($result) {
			$this->message = __("Record deleted successfully.");
			if ($_id > 0) { return true; }
		} else {
			$this->message = __("Record deletion failed.");
			if ($_id > 0) { return true; }
		}
		$action = "";
		$this->view(); $this->display_message();
	} // end function _del

	// Method: del
	//
	//	Wrapper for _del. This exists so that FreeMED modules
	//	can override the basic del while still
	//	having access to the low-level functionality.
	//
	// See Also:
	//	<_del>
	//
	function del () { $this->_del(); }

	// Method: _mod
	//
	//	Basic superclass modification routine.
	//
	// Parameters:
	//
	//	$_param - (optional) Associative array of values. If
	//	specified, _mod will run quiet. The associative array
	//	is in the format of sql_name => sql_value.
	//
	// Returns:
	//
	//	Nothing if there are no parameters. If $_param is
	//	specified, _mod will return boolean true or false
	//	depending on whether it is successful.
	//
	// See Also:
	//	<mod>
	//
	function _mod ($_param = NULL) {
		global $display_buffer, $action;
		foreach ($GLOBALS AS $k => $v) { global ${$k}; }

		// If there are parameters, import them into the global
		// scope, then set their values
		if (is_array($_param)) {
			foreach ($_param AS $k => $v) {
				global ${$k};
				${$k} = $v;
			}
		}

		$result = $GLOBALS['sql']->query (
			$GLOBALS['sql']->update_query (
				$this->table_name,
				$this->variables,
				array (
					"id"	=>	$id
				)
			)
		);

		// If we were passed a parameter, we don't go to doing
		// anything fancy, just return a true or false.
		if ($result) {
			$this->message = __("Record modified successfully.");
			if (is_array($_param)) { return true; }
		} else {
			$this->message = __("Record modification failed.");
			if (is_array($_param)) { return false; }
		}

		$action = "";
		$this->view(); $this->display_message();
	} // end function _mod

	// Method: mod
	//
	//	Wrapper for _mod. This exists so that FreeMED modules
	//	can override the basic mod while still
	//	having access to the low-level functionality.
	//
	// See Also:
	//	<_mod>
	//
	function mod() { $this->_mod(); }

	// function add/modform
	// - wrappers for form
	function addform () { $this->form(); }
	function modform () { $this->form(); }

	// Method: form
	//
	//	Superclass stub for basic add/modify form capabilities.
	//	Performs no useful task, and should be overridden with
	//	the methods producing an addition/modification form.
	//
	function form () {
		global $display_buffer;
		global $action, $id, $sql;

		if (is_array($this->form_vars)) {
			foreach ($this->form_vars AS $k => $v) { global ${$v}; }
		} // end if is array

		switch ($action) {
			case "addform":
				break;

			case "modform":
				$result = $sql->query ("SELECT * FROM ".$this->table_name.
					" WHERE ( id = '".prepare($id)."' )");
				$r = $sql->fetch_array ($result);
				extract ($r);
				break;
		} // end of switch action
		
	} // end function form

	// function view
	// - view stub
	function view () {
		global $display_buffer;
		$display_buffer .= freemed_display_itemlist (
			$GLOBALS['sql']->query (
				"SELECT ".$this->order_fields." ".
				"FROM ".$this->table_name." ".
				freemed::itemlist_conditions()." ".
				"ORDER BY ".$this->order_fields
			),
			"module_loader.php",
			$this->form_vars,
			array ("", __("NO DESCRIPTION")),
			"",
			"t_page"
		);
	} // end function view

	// Method: picklist
	//
	//	Generic picklist for XML-RPC.
	//
	// Parameters:
	//
	//	$criteria - (optional) Hash of criteria fields to narrow
	//	the search
	//
	// Returns:
	//
	//	Array of hashes
	//
	function picklist ( $criteria = NULL ) {
		// Do not execute if there is no field map
		if (!is_array($this->rpc_field_map)) {
			return false;
		}

		$result = $GLOBALS['sql']->query("SELECT * FROM ".
			$this->table_name." ORDER BY ".
			$this->order_by);
		if (!$GLOBALS['sql']->results($result)) {
			return CreateObject('PHP.xmlrpcresp',
				CreateObject('PHP.xmlrpcval', 'none', 'string')
			);
		}
		return rpc_generate_sql_hash(
			$this->table_name,
			array_merge(
				$this->rpc_field_map,
				array ( 'id' => 'id' )
			),
			'ORDER BY '.$this->order_by
		);
	} // end method picklist

	// Method: widget
	//
	//	Generic widget code to allow a picklist-based widget for
	//	simple modules. Should be overridden for more complex tasks.
	//
	//	This function uses $this->widget_hash, which contains field
	//	names surrounded by '##'s.
	//
	// Parameters:
	//
	//	$varname - Name of the variable that the widget's data is
	//	passed in.
	//
	//	$conditions - (optional) Additional clauses for SQL WHERE.
	//	defaults to none.
	//
	// Returns:
	//
	//	XHTML-compliant picklist widget.
	//
	function widget ( $varname, $conditions = false ) {
		$query = "SELECT * FROM ".$this->table_name." WHERE ( 1 = 1) ".
			( $conditions ? "AND ( ".$conditions." ) " : "" ).
			"ORDER BY ".$this->order_field;
		$result = $GLOBALS['sql']->query($query);
		while ($r = $GLOBALS['sql']->fetch_array($result)) {
			if (!(strpos($this->widget_hash, "##") === false)) {
				$key = '';
				$hash_split = explode('##', $this->widget_hash);
				foreach ($hash_split AS $_k => $_v) {
					if (!($_k & 1)) {
						$key .= prepare($_v);
					} else {
						$key .= prepare($r[$_v]);
					}
				}
			} else {
				$key = $this->widget_hash;
			}
			$return[$key] = $r['id'];
		}
		return html_form::select_widget($varname, $return);
	} // end method widget

	// Method: _setup
	//
	//	Internal method called by the module superclass, which
	//	executes initial table creation from <create_table> and
	//	initial data import from <freemed_import_stock_data>.
	//
	function _setup () {
		global $display_buffer;
		if (!$this->create_table()) return false;
		return freemed_import_stock_data ($this->table_name);
	} // end function _setup

	// Method: create_table
	//
	//	Creates the initial table definition required by this
	//	module to function properly. Relies on the
	//	table_definition class variable, and will not execute
	//	if it is not present.
	//
	// Returns:
	//
	//	Boolean, false if failed.
	//
	function create_table () {
		global $display_buffer;
		if (!isset($this->table_definition)) return false;
		$query = $GLOBALS['sql']->create_table_query(
			$this->table_name,
			$this->table_definition,
			( is_array($this->table_keys) ?
				array_merge(array("id"), $this->table_keys) :
				array("id")
			)
		);
		$result = $GLOBALS['sql']->query ($query);
		return !empty($result);
	} // end function create_table

} // end class MaintenanceModule

?>
