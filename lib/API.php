<?php
 // $Id$
 // note: complete list of included functions for all modules
 //       basically to cut down on includes, and make everything
 //       a little easier
 // code: jeff b (jeff@univrel.pr.uconn.edu)
 //       max k <amk@span.ch>
 //       adam (gdrago23@yahoo.com)
 // lic : GPL, v2
 // $Log$
 // Revision 1.37  2001/10/18 20:41:51  rufustfirefly
 // removed more dead code
 //
 // Revision 1.36  2001/10/17 20:47:36  rufustfirefly
 // another day of changes... or two...
 //
 // Revision 1.35  2001/10/12 14:59:59  rufustfirefly
 // added Log tag
 //

if (!defined("__API_PHP__")) {

define ('__API_PHP__', true);

// function freemed_bar_alternate_color
function freemed_bar_alternate_color ($cur_color="") {
	global $bar_start_color, $bar_alt_color;

	switch ($cur_color) {
		case $bar_start_color:
		return $bar_alt_color;
		break;
		
		case $bar_alt_color:
		default:
		return $bar_start_color;
		break;
	} // end color decision switch
} // end function freemed_bar_alternate_color

// function freemed_check_access_for_facility
function freemed_check_access_for_facility ($facility_number) {
	global $SESSION;

	// Separate out authdata
	$authdata = $SESSION["authdata"];

	// Root has all access...
	if ($authdata["user"]==1) return true;

	// Grab the authorizations field
	$f_fac = freemed_get_link_field ($authdata["user"], "user", "userfac");

	// No facility, assume no access restrictions
	if ($facility_number == 0) return true;

	// If it's an "ALL" or it is found, return true
	if ((fm_value_in_string($f_fac, "-1")) OR
		(fm_value_in_string($f_fac, $facility_number)))
		return true;

    	// Default to false
	return false;
} // end function freemed_check_access_for_facility

// function freemed_check_access_for_patient
function freemed_check_access_for_patient ($patient_number) {
	global $SESSION;

	// Grab authdata
	$authdata = $SESSION["authdata"];

	// Root has all access...
	if ($authdata["user"]==1) return true;

	// Grab auth information from db
	$f_user   = freemed_get_link_rec ($authdata["user"], "user");

	// Get data records in question for the user
	$f_fac    = $f_user ["userfac"   ];
	$f_phy    = $f_user ["userphy"   ];
	$f_phygrp = $f_user ["userphygrp"];

	// Retrieve patient record
	$f_pat    = freemed_get_link_rec ($patient_number, "patient");

	// check for universal access
	if ((fm_value_in_string ($f_fac,    "-1")) OR
		(fm_value_in_string ($f_phy,    "-1")) OR
		(fm_value_in_string ($f_phygrp, "-1")))
		return true;

	// Check for physician in any physician fields
	if (($f_pat["ptpcp"]>0) AND
		(fm_value_in_string ($f_phy, $f_pat["ptpcp"])))
		return true;
	if (($f_pat["ptphy1"]>0) AND
		(fm_value_in_string ($f_phy, $f_pat["ptphy1"])))
		return true;
	if (($f_pat["ptphy2"]>0) AND
		(fm_value_in_string ($f_phy, $f_pat["ptphy2"])))
		return true;
	if (($f_pat["ptphy3"]>0) AND
		(fm_value_in_string ($f_phy, $f_pat["ptphy3"])))
		return true;
	if (($f_pat["ptdoc"]>0) AND
		(fm_value_in_string ($f_phy, $f_pat["ptdoc"])))
		return true;

    	// Default to false
	return false;
} // end function freemed_check_access_for_patient

// function freemed_close_db
function freemed_close_db ($_null=" ")
{
  global $sql;
  //$sql->close(); // close the link
} // end function freemed_close_db

// function freemed_config_value
function freemed_config_value ($config_var) {
	static $_config;
	global $sql;
 
 	// Set to cache values
 	if (!isset($_config)) {
		$query = $sql->query("SELECT * FROM config");

		// If the table doesn't exist, skip out
		if (!$query) return false;

		// Loop through results
		while ($r = $sql->fetch_array($query)) {
			$_config[stripslashes($r[c_option])] =
				stripslashes($c_value);
		} // end of looping through results
	} // end of caching

	// Return from cache
	return $_config["$config_var"];
} // end function freemed_config_value

// function freemed_display_arraylist
function freemed_display_arraylist ($var_array, $xref_array="") {
  $buffer = ""; // return a buffer
  if (!is_array($var_array)) // we've been passed an empty array
    return "";
  
  $buffer .= "
    <TABLE WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2 BORDER=1>
  ";
  $first = true;
  $buffer .= "<TR>";
  while (list($key, $val)=each($var_array)) { // for each variable
    if ($first) $main=$val; // first item is index...
    global ${$val}; // global all the arrays, remember to
                  // use ${$foo}[0] for subscripts!
    $first = false;
    $buffer .= "
      <TD ALIGN=CENTER>
        <B>$key</B>
      </TD>
    ";
  } // while... displaying header
  $buffer .= "
      <TD ALIGN=CENTER>
        "._("Remove")."
      </TD>
      <TD ALIGN=CENTER>
        "._("Modify")." ( "._("None")."
	<INPUT TYPE=RADIO NAME=\"".$main."mod\" VALUE=\"-1\" CHECKED> ) 
      </TD>
     </TR>
  ";

  global ${$main."_active"}; // e.g., 'ptins_active', is set outside func
  // use ${$foo}[0] for arrays!!

  if (!is_array(${$main}))
    return ($buffer."
      <TR><TD COLSPAN=".(count($var_array) + 2)." ALIGN=CENTER>
        "._("No Items")."
      </TD></TR>
     </TABLE>"); // make sure it's safe if there are no items

  reset(${$main});
  while(list($i, $mainval) = each(${$main})) {
    if (!isset($mainval)) {echo "{[$i]}";continue;} // skip if removed
    $this_active = ${$main."_active"}[$i]; // is this item active?
    if ($this_active)
      $buffer .= "<TR BGCOLOR=".($bar=freemed_bar_alternate_color($bar)).">";
      
    reset($var_array); if (is_array($xref_array)) reset($xref_array);
    while (list($key,$val)=each($var_array)) { // each variable
      $item_text = ${$val}[$i];
      if (is_array($xref_array)) {
        list($x_key, $x_val) = each($xref_array);
	if (strlen($x_val) > 0)
          $item_text=freemed_get_link_field(${$val}[$i], $x_key, $x_val);
      } // grab the xref if necessary
      if ($this_active)
        $buffer .= "
          <TD ALIGN=CENTER>
            <FONT SIZE=\"-1\">
	    ".((strlen($item_text)>0) ? $item_text : "&nbsp;")."
	    </FONT>
          </TD>";
      $buffer .= "
        <INPUT TYPE=HIDDEN NAME=\"$val"."[$i]\" VALUE=\"".${$val}[$i]."\">
      "; // always add the hidden tags!
    } // while each variable
    
    if ($this_active)
      $buffer .= "
      <TD ALIGN=CENTER>
        <INPUT TYPE=CHECKBOX NAME=\"".$main."del[$i]\">
      </TD>
      <TD ALIGN=CENTER>
        <INPUT TYPE=RADIO NAME=\"".$main."mod\" VALUE=\"$i\"> 
      </TD>
     </TR>";
  } // for each item in the stack
  
  $buffer .= "</TABLE>";
  return $buffer;
} // end function freemed_display_arraylist

// function freemed_display_actionbar
function freemed_display_actionbar ($this_page_name="", $__ref="") {
	global $page_name, $patient, $_ref, $module;

	$buffer = "";

	if (!empty($_ref)) $__ref = $_ref;

	if ($this_page_name=="") $this_page_name = $page_name;

	if (!empty($__ref)) {
		$_ref="main.php";
	  } // if no ref, then return to home page...

    // show the actual bar, build with page_name reference
    // and global variables
	$buffer .= "
    <TABLE BGCOLOR=\"#000000\" WIDTH=\"100%\" BORDER=0
     CELLSPACING=0 CELLPADDING=3>
    <TR BGCOLOR=\"#000000\">
    <TD ALIGN=LEFT><A HREF=\"$this_page_name?module=".urlencode($module)."&".
	"action=addform".
     ( !empty($patient) ? "&patient=".urlencode($patient) : "" )
     ."\"><FONT COLOR=\"#ffffff\" FACE=\"Arial, Helvetica, Verdana\"
     SIZE=-1><B>"._("ADD")."</B></FONT></A></TD>
    <TD WIDTH=\"30%\">&nbsp;</TD>
    <TD ALIGN=RIGHT><A HREF=\"$__ref\"
     ><FONT COLOR=\"#ffffff\" FACE=\"Arial, Helvetica, Verdana\"
     SIZE=-1><B>"._("RETURN TO MENU")."</B></FONT></A></TD>
    </TR></TABLE>
  	";
	return $buffer;

} // end function freemed_display_actionbar

// function freemed_display_itemlist
function freemed_display_itemlist ($result, $page_link, $control_list, 
                           $blank_list, $xref_list="",
			   $cur_page_var="this_page",
			   $index_field="", $flags=-1)
{
  global $_ref, $record_name;
  global $modify_level, $delete_level, $patient, $action, $module;
  global $page_name, $$cur_page_var, $max_num_res;
  global $_s_field, $_s_val, $sql;

  //echo "page name $page_name this $this->page_name module $module<BR>";
  
  if ($flags==-1) $flags=(ITEMLIST_MOD|ITEMLIST_DEL);

  // pull current page name
  if (empty ($page_link)) {
    $parts = explode("?", basename($GLOBALS["REQUEST_URI"]));
    $page_link = $parts[0];
  } // end of pull current page name

  if ( (isset($module)) AND (!empty($module)) )
  {
	// if we are in a module pull the module loader
    // name for paging
    $parts = explode("?", basename($GLOBALS["REQUEST_URI"]));
    $page_name = $parts[0];
  }
  

  // TODO: make sure $control_list is an array, verify the inputs, yadda yadda

  $num_pages = ceil($sql->num_rows($result)/$max_num_res);
  if ($$cur_page_var<1 OR $$cur_page_var>$num_pages) $$cur_page_var=1;

  if (strlen($$cur_page_var)>0) { // there's an offset
    for ($i=1;$i<=($$cur_page_var-1)*$max_num_res;$i++) {
      $herman = $sql->fetch_array($result); // offset the proper number of rows
    }
  }

  $buffer="";

  $buffer .= "
    <!-- Begin itemlist Table -->
    <TABLE WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2 BORDER=0
     ALIGN=CENTER VALIGN=MIDDLE BGCOLOR=\"#777777\">
    <TR>
     <TD ALIGN=CENTER>
      <FONT SIZE=+1 COLOR=\"#ffffff\">"._("$record_name")."</FONT>
     </TD>
    </TR>".
    
   ( ((strlen($cur_page_var)>0) AND ($num_pages>1)) ? "
   <TR ALIGN=CENTER><TD BGCOLOR=\"#000000\">
    <TABLE BORDER=0 CELLPADDING=2 CELLSPACING=0>
     <FORM METHOD=POST ACTION=\"$page_name\">
    ".
    
    (($$cur_page_var>1) ? "
    <TR><TD>
     <FONT COLOR=\"#ffffff\">
     <A HREF=\"$page_name?$cur_page_var=".($$cur_page_var-1).
     ((strlen($_s_field)>0) ? "&_s_field=$_s_field&_s_val="
       .prepare($_s_val)."" : "").
     "&module=$module&action=$action\"><FONT COLOR=\"#ffffff\">
        "._("Previous")."
     </FONT></A>
    </TD>
    " : "" )
    
    ."<TD>
     <FONT COLOR=\"#ffffff\">
     "._("Page ".$$cur_page_var." of $num_pages")."
     </FONT>
     <INPUT TYPE=HIDDEN NAME=\"action\"  VALUE=\"".prepare($action)."\" >
     <INPUT TYPE=HIDDEN NAME=\"module\"  VALUE=\"".prepare($module)."\" >
     <INPUT TYPE=HIDDEN NAME=\"patient\" VALUE=\"".prepare($patient)."\">
     ".fm_number_select($cur_page_var, 1, $num_pages)."
     <INPUT TYPE=SUBMIT VALUE=\""._("Go")."\">
    </TD>".
    
    (($$cur_page_var<$num_pages) ? "
    <TD>
     <FONT COLOR=\"#ffffff\">
     <A HREF=\"$page_name?$cur_page_var=".($$cur_page_var+1).
     ((strlen($_s_field)>0) ? "&_s_field=$_s_field&_s_val="
       .prepare($_s_val)."" : "").
     "&module=$module&action=$action\"><FONT COLOR=\"#ffffff\">
        "._("Next")."
     </FONT></A>
    </TD></TR>
    " : "" )
    
    ."
     </FORM>
    </TABLE>
   </TD></TR>
    " : "" )
    
    ."<TR><TD>
    ".freemed_display_actionbar($page_link)."
    </TD></TR>
    <TR><TD>
  ";
  // end header

  $buffer .= "
    <TABLE WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2 BORDER=0
     ALIGN=CENTER VALIGN=MIDDLE>
    <TR>
  ";
  while (list($k,$v)=each($control_list)) {
    $buffer .= "
      <TD BGCOLOR=\"#000000\">
       <FONT COLOR=\"#ffffff\">$k&nbsp;</FONT>
      </TD>
    ";
  }
  if ($flags != 0)
  {
  $buffer .= "
      <TD BGCOLOR=\"#000000\">
       <FONT COLOR=\"#ffffff\">"._("Action")."</FONT>
      </TD>
	  </TR>
  ";
  }
  else
  	$buffer .= "<TD BGCOLOR=\"#000000\"></TD></TR>";
 
  if ($sql->num_rows($result)>0) 
   while ($this_result = $sql->fetch_array($result) AND 
      ((strlen($cur_page_var)>0) ? ($on_this_page < $max_num_res) : (1)) ) {
    $on_this_page++;
    $first = true; // first item in the list has 'view' link
    $buffer .= "
    <TR BGCOLOR=".($_alternate=freemed_bar_alternate_color($_alternate)).">
    ";
    reset($control_list); // it's already each'd the arrays, 
    if (is_array($xref_list)) 
      reset($xref_list);    // but we have to do it again for the next iteration
    $field_num=0;
    while (list($k,$v)=each($control_list)) {
      $is_xref=false;
      if (is_array($xref_list)) {
        reset($xref_list);
        $xref_k = $xref_v = "";
        for ($i=0;$i<=$field_num;$i++)
          list ($xref_k, $xref_v) = each($xref_list);
        // the proper item is now in $xref_{k,v}
        if (strlen($xref_v)>1) {
          $is_xref=true;
          $xref_item=freemed_get_link_field($this_result[$v],
                                                    $xref_k,$xref_v);
          $item_text = ( (strlen($xref_item)<1) ?
                         prepare($blank_list[$field_num]) :
                         prepare($xref_item) );
        }
      } // if there are any xrefs in the table
      if (!$is_xref) { // not an xref item 
        $item_text = ( (strlen($this_result[$v])<1)?
                       prepare($blank_list[$field_num])  :
                       prepare($this_result[$v]) ); 
      }
      if ($first) {
        $first = false;
        $buffer .= "
      <TD>
        <A HREF=\"$page_link?patient=$patient&action=display&id=".
	"$this_result[id]&module=$module\"
	  >$item_text</FONT></A>&nbsp;
      </TD>
        ";
      } else {
        $buffer .= "
      <TD>
        $item_text&nbsp;
      </TD>
        ";
      }
    $field_num++;
    } // while each data field
    
    $buffer .= "
      <TD>
    ";
    if ($flags & ITEMLIST_VIEW) {
      $buffer .= "
        <A HREF=\"$page_link?module=$module&patient=$patient&action=view&id=".
	"$this_result[id]\">"._("VIEW")."</A>&nbsp;
      ";
    }
    if (freemed_get_userlevel()>$database_level AND 
         ($flags & ITEMLIST_MOD)) {
      $buffer .= "
        <A HREF=\"$page_link?module=$module&patient=$patient&action=modform&id=".
	"$this_result[id]\">"._("MOD")."</A>&nbsp;
      ";
    }
    if (freemed_get_userlevel()>$delete_level AND
         ($flags & ITEMLIST_DEL)) {
      $buffer .= "
        <A HREF=\"$page_link?patient=$patient&module=$module&action=delete&id=".
	"$this_result[id]\">"._("DEL")."</A>&nbsp;
      ";
    }
    
    $buffer .= "
      &nbsp;</TD>
    </TR>
    ";
   } // while each result-row
  else { // no items to display
   $buffer .= "
    <TR BGCOLOR=".($_alternate=freemed_bar_alternate_color($_alternate)).">
     <TD COLSPAN=".(count($control_list)+1)." ALIGN=CENTER>
      <I>No "._($GLOBALS["record_name"])."</I>
     </TD>
    </TR>
   ";
  } // if no items to display
   
  $buffer .= "
    </TABLE>
   </TD></TR>
   <TR><TD>
  ";
  
  // searchbox
 if ($num_pages>1) {
  $buffer .= "
    <TABLE WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=2 BORDER=0>
    <TR BGCOLOR=\"#000000\">
    <FORM METHOD=POST ACTION=\"".prepare($page_name)."\">
     <TD ALIGN=CENTER>
      <SELECT NAME=\"_s_field\">
  ";
  reset($control_list);
  while (list($c_k, $c_v) = each($control_list))
    $buffer .= "<OPTION VALUE=\"$c_v\">$c_k\n";
  $buffer .= "
      </SELECT>
      <FONT COLOR=\"#ffffff\"> "._("contains")." </FONT>
      <INPUT TYPE=HIDDEN NAME=\"module\" VALUE=\"".prepare($module)."\">
      <INPUT TYPE=HIDDEN NAME=\"$cur_page_var\" VALUE=\"1\">
      <INPUT TYPE=TEXT NAME=\"_s_val\">
      <INPUT TYPE=SUBMIT VALUE=\""._("Search")."\">
     </TD>
    </FORM>
    </TR>
    </TABLE>
   </TR></TD>
   <TR><TD>
  ";
 } // no searchbox for short-short lists
  // end searchbox
  
  // footer
  $buffer .= freemed_display_actionbar($page_link)
    ."</TD></TR>
    </TABLE>
    <!-- End itemlist Table -->
  ";

  return $buffer; // gotta remember this part!
}

// function freemed_display_facilities (selected)
function freemed_display_facilities ($param="", $default_load = false,
                                     $intext="")
{
  global $default_facility, $sql;

  $buffer = "";

  switch ($intext) {
   case "0": // internal
    $intextquery = "WHERE psrintext='0'";
    break;
   case "1": // external
    $intextquery = "WHERE psrintext='1'";
    break;
   default:
    $intextquery = "";
  }

  // list doctors in SELECT/OPTION tag list, and
  // leave doctor selected who is in param
  $buffer .= "
    <OPTION VALUE=\"0\"".
     ( ($param == 0) ? " SELECTED" : "" ).">"._("NONE SELECTED")."
  ";
  $query = "SELECT * FROM facility
            $intextquery
            ORDER BY psrname,psrnote";
  $result = $sql->query ($query);
  if (!$result) {
    // don't do anything...! 
  } else { // exit if no more docs
    while ($row=$sql->fetch_array($result)) {
      if ((strlen($param)>0) AND ($param != "0") AND
          ($param == $row["id"])) 
        $selected_var = " SELECTED";
      else
        $selected_var = ""; // or not...

      $_psr_name = stripslashes($row["psrname"]);
      $_psr_note = $row["psrnote"];
      $_psr_id   = $row["id"];

      if (($_psr_id==$default_facility) AND ($default_facility>0))
            { $selected_var = " SELECTED"; }

      if (strlen($_psr_note)<1) $__psr_note = "";
        else $__psr_note = "[$_psr_note]";

      $buffer .= "
        <OPTION VALUE=\"$_psr_id\" $selected_var 
         >$_psr_name $__psr_note ".
         ( $debug ? "[$_psr_id]" : "" )."
      "; // end of actual output
    } // while there are more results...
  }
  return $buffer;
} // end function freemed_display_facilities

// function freemed_display_physicians
//   displays physicians selectable in <SELECT>
//   list with $param selected
function freemed_display_physicians ($param, $intext="") {
	$buffer = "";

	// list doctors in SELECT/OPTION tag list, and
	// leave doctor selected who is in param
	$buffer .= "
		<OPTION VALUE=\"0\">"._("NONE SELECTED")."
	";
	$query = "SELECT * FROM physician ".
		( ($intext != "") ? " WHERE phyref='$intext'" : "" ).
		"ORDER BY phylname,phyfname";
	$result = $sql->query ($query);
	if (!$sql->results($result)) {
		// don't do anything...! 
	} else { // exit if no more docs
		while ($row = $sql->fetch_array($result)) {
			$buffer .= "
			<OPTION VALUE=\"$row[id]\" ".
			( ($row[id] == $param) ? "SELECTED" : "" ).
			">".prepare("$row[phylname], $row[phyfname]")."
			"; // end of actual output
		} // while there are more results...
	}
	return $buffer;
} // end function freemed_display_physicians

///////////////////////////////////////////////////
// function freemed_display_printerlist
// displays printers from the database
function freemed_display_printerlist ($param)
{
  global $sql;

  // list printers in SELECT/OPTION tag list, and
  // leave printer selected who is in param
  echo "
    <OPTION VALUE=\"0\">"._("NONE SELECTED")."
  ";
  $query = "SELECT * FROM printer ORDER BY ".
     "prnthost, prntname";
  $result = $sql->query ($query);
  if (!$result) {
    // don't do anything...! 
  } else { // exit if no more printers
    while ($row=$sql->fetch_array($result)) {
      echo "
        <OPTION VALUE=\"$row[id]\" ".
	( ($param == $row[id]) ? "SELECTED" : "" ).
        ">".prepare("$row[prnthost] $row[prntname]")."
      "; // end of actual output
    } // while there are more results...
  }
} // end function freemed_display_printerlist

// ---------------  19990815
// function freemed_display_simplereports
//   displays simple reports templates selectable in <SELECT>
//   list with $param selected
function freemed_display_simplereports ($param="") {
  // list templates in SELECT/OPTION tag list, and
  // leave report selected who is in param
  echo "
    <OPTION VALUE=\"0\">"._("NONE SELECTED")."
  ";
  $query = "SELECT * FROM simplereport ORDER BY ".
     "sr_type, sr_label";
  $result = $sql->query ($query);
  if (!$result) {
    // don't do anything...! 
  } else { // exit if no more docs
    while ($row=$sql->fetch_array($result)) {
      echo "
        <OPTION VALUE=\"$_sr_id\" ".
	( ($row[id] == $param) ? "SELECTED" : "" ).
        ">".prepare("$row[sr_label], $row[sr_type]")."
      "; // end of actual output
    } // while there are more results...
  }
} // end function freemed_display_simplereports

// function freemed_display_selectbox
function freemed_display_selectbox ($result, $format, $param="") {
	global ${$param}; // so it knows to put SELECTED on properly
	global $sql; // for database connection

	static $var; // array of $result-IDs so we only go through them once
	static $count; // count of results

	if (!isset($var["$result"])) {
		if ($result) {
			$count["$result"] = $sql->num_rows($result);
			while ($var["$result"][] = $sql->fetch_array($result));
		} // non-empty result
	} // if we haven't gone through this list yet
 
	$buffer = "";
	if ($count["$result"]<1) { 
		$buffer .= _("NONE")." ".
			"<INPUT TYPE=HIDDEN NAME=\"$param\" VALUE=\"0\">";
		return $buffer; // do nothing!
	} // if no result

	$buffer .= "
		<SELECT NAME=\"$param\">
		<OPTION VALUE=\"0\">"._("NONE SELECTED")."
	";
	
	reset($var["$result"]); // if we're caching it, we have to reset it!
	// no null values!
	while ( (list($pickle,$item) = each($var["$result"])) AND ($item[id])) {
		// START FORMAT-FETCHING
		// Odd members are variable names
		$format_array = explode("#",$format);
		while (list($index,$str) = each($format_array)) {
			// ignore the evens!
			if ( !($index & 1) ) continue;
			// can't just change $str!
			$format_array[$index] = $item[$str];
		} // while replacing each variable name
		// put it back together
		$this_format = join("", $format_array);
		// END FORMAT-FETCHING    

		$buffer .= "
		<OPTION VALUE=\"$item[id]\" ".
		( ($item[id] == $$param) ? "SELECTED" : "" ).
		">".prepare($this_format)."\n";
	} // while fetching result
	$buffer .= "
	</SELECT>
	";
  
	return $buffer;
} // end function freemed_display_selectbox

// function freemed_export_stock_data
function freemed_export_stock_data ($table_name, $file_name="") {
	global $sql, $default_language, $cur_date_hash, $debug;

	$physical_file = PHYSICAL_LOCATION . "/data/" . $default_language . 
		"/" .  $table_name . "." . $default_language . "." . 
		$cur_date_hash;

	//if (strlen ($file_name) > 2) $physical_file = $file_name;

	//if (file_exists ($physical_file)) { return false; } // fix this later

	$query = "SELECT * FROM ".addslashes($table_name)." ".
		"INTO OUTFILE '".addslashes($physical_file)."' ".
		"FIELDS TERMINATED BY ',' ".
		"OPTIONALLY ENCLOSED BY '' ".
		"ESCAPED BY '\\\\'";

	if ($debug) echo "<BR> query = \"$query\" <BR> \n";

	$result = $sql->query ($query);

	if ($debug) echo "<BR> result = \"$result\" <BR> \n";

	return $result;
} // end function freemed_export_stock_data

// function freemed_get_date_next
//  return the next valid date (YYYY-MM-DD)
function freemed_get_date_next ($cur_dt) {
	global $cur_date;

	$y = substr ($cur_dt, 0, 4); // get year
	$m = substr ($cur_dt, 5, 2); // get month
	$d = substr ($cur_dt, 8, 2); // get date

	// check for validity of given date... if not, cur_date
	if (!checkdate($m, $d, $y)) { 
		$y = substr ($cur_date, 0, 4);
		$m = substr ($cur_date, 5, 2);
		$d = substr ($cur_date, 8, 2); 
	}

	if (!checkdate($m, $d + 1, $y)) { // roll day?
		if (!checkdate($m + 1, 1, $y)) { // roll month?
			// roll year
			return date ("Y-m-d", mktime (0,0,0,1,1,$y+1));
		} else {
			// roll month
			return date ("Y-m-d", mktime (0,0,0,$m+1,1,$y));
		} // end checking roll month?
	} else { // checking roll day
		// roll day
		return date ("Y-m-d", mktime (0,0,0,$m,$d+1,$y));
	} // end checking roll day
} // end function freemed_get_date_next

// function freemed_get_date_prev
//   returns the previous date
function freemed_get_date_prev ($cur_dt) {
	global $cur_date;

	$y = substr ($cur_dt, 0, 4); // year
	$m = substr ($cur_dt, 5, 2); // month
	$d = substr ($cur_dt, 8, 2); // day 

	if (!checkdate ($m, $d, $y)) {
		$y = substr ($cur_date, 0, 4);
		$m = substr ($cur_date, 5, 2);
		$d = substr ($cur_date, 8, 2);
	} // if not right, use current date

	if (($d==1) AND ($m>1)) { // if first day...
		$d = 31; $m--; // roll back
		  // while day too high, decrease
		while (!checkdate ($m, $d, $y)) $d--;
		return date ("Y-m-d",mktime(0,0,0,$m,$d,$y));
	} else if (($d==1) AND ($m==1)) { 
		// roll back year
		$m=12; $y--; $d=31;
		return date ("Y-m-d",mktime(0,0,0,$m,$d,$y));
	} else { // checking for day
		// roll back day
		$d--;
		return date ("Y-m-d",mktime(0,0,0,$m,$d,$y));
	} // end checking for first day
} // end function freemed_get_date_prev

// function freemed_get_link_rec
//   return the entire record as an array for
//   a link
function freemed_get_link_rec($id="0", $table="") {
	global $database, $sql;

	if (empty($table)) {
		return "";
	}

	$result = $sql->query("SELECT * FROM ".addslashes($table)." WHERE".
		" id='".addslashes($id)."'"); // get just that record
	return $sql->fetch_array($result); // return the array

} // end function freemed_get_link_rec

// function freemed_get_link_field
//   return a particular field from a link...
function freemed_get_link_field($id, $db, $field="id") {
	global $database;

	if (strlen($db)<1) { return NULL; }

	// Retrieve the entire record
	$this_array = freemed_get_link_rec($id, $db);

	// Return just the key asked for
	return $this_array["$field"];
} // end function freemed_get_link_field

// function freemed_get_userlevel
//   returns user level (1-10)
//   (assumes 1 if not found, 9 if root)
function freemed_get_userlevel ($f_cookie="") {
	global $database, $sql, $SESSION;
	static $userlevel;

	// Extract authdata from SESSION
	$authdata = $SESSION["authdata"];

	// Check for cached userlevel
	if (isset($userlevel)) return $userlevel;

	// Check for null user
	if (($authdata["user"]<1) or (!isset($authdata["user"]))) {
		$userlevel = 0;
		return 0; // if no user, return 0
	}

	if ($authdata["user"] == 1) {
		$userlevel = 10;
		return 10; // if root, give superuser access
	} else {
		$result = $sql->query("SELECT * FROM user
			WHERE id='".addslashes($authdata["user"])."'");

		// Check for improper results, return "unauthorized"
		if (!$sql->results($result) or ($sql->num_rows($result) != 1)) {
			$userlevel = 1;
			return 1;
		}

		// Get results
		$r = $sql->fetch_array($result);

		// Set $userlevel (which is cached)
		$userlevel = $r["userlevel"];

		// Return the answer...
		return $userlevel;
	} // end else loop checking for name

} // end function freemed_get_userlevel

// function freemed_import_stock_data
//  import stock data from data/$language directory
function freemed_import_stock_data ($table_name) {
	global $default_language, $sql;

	// Produce a physical location
	$physical_file = PHYSICAL_LOCATION . "/data/" . $default_language .
		"/" .  $table_name . "." . $default_language . ".data";

	// Die if the phile doesn't exist
	if (!file_exists($physical_file)) return false;

	// Create the query
	$query = "LOAD DATA LOCAL INFILE '$physical_file' INTO
		TABLE $table_name
		FIELDS TERMINATED BY ','";
           
	$result = $sql->query ($query); // try doing it

	return $result; // send the results home...
} // end function freemed_import_stock_data

// function freemed_log
/*
 TODO: FIX ME!
function freemed_log ($db_name, $record_number, $comment) {
	global $cur_date, $sql, $SESSION;

	$f_auth = explode (":", $f_cookie);
	$f_user = $f_auth [0];  // extract the user number

	$query = "INSERT INTO log VALUES ( '$cur_date',
	$f_user', '$db_name', '$record_number', '$comment', NULL )";
	$result = $sql->query ($query); // perform addition
	return true;  // return true
} // end function freemed_log
*/

// function freemed_module_check
function freemed_module_check ($module, $minimum_version="0.01")
{
	static $_config; global $sql;

	// cache all modules  
	if (!is_array($_config)) {
		unset ($_config);
		$query = $sql->query("SELECT * FROM module");
		while ($r = $sql->fetch_array($query)) {
			extract ( $r );
			$_config["$module_name"] = $module_version;
		} // end of while results
	} // end caching modules config

	// check in cache for version > minimum_version
	// (return ($_config["$module"] > $minimum_version))
	return version_check($_config["$module"], $minimum_version);
} // end function freemed_module_check

// function freemed_module_version
function freemed_module_version ($module) {
	static $_config; global $sql;

	// cache all modules  
	if (!is_array($_config)) {
		unset ($_config);
		$query = $sql->query("SELECT * FROM module");
		while ($r = $sql->fetch_array($query)) {
			extract ( $r );
			$_config["$module_name"] = $module_version;
		} // end of while results
	} // end caching modules config

	// check in cache for version
	return $_config["$module"];
} // end function freemed_module_version

// function freemed_module_register
function freemed_module_register ($module, $version)
{
	global $sql;

	// check for modules  
	if (!freemed_module_check($module, $version)) {
		$query = $sql->query($sql->insert_query(
			"module",
			array(
				"module_name"		=>	$module,
				"module_version"	=>	$version
			)
		));
		return (!empty($query));
	} // end caching modules config

	return true;
} // end function freemed_module_register

// function freemed_multiple_choice
function freemed_multiple_choice ($sql_query, $display_field, $select_name,
  $blob_data, $display_all=true) {
	global $sql;
	$buffer = "";

	$brackets = "[]";
	$result = $sql->query ($sql_query); // check
	$all_selected = fm_value_in_string ($blob_data, "-1");

	$buffer .= " 
	<SELECT NAME=\"$select_name$brackets\" MULTIPLE SIZE=5>
	";
	if ($display_all) $buffer .= "
		<OPTION VALUE=\"-1\" ".
		($all_selected ? "SELECTED" : "").">"._("ALL")."
	"; // if there is nothing...

	if ( $sql->results ($result) ) 
		while ($r = $sql->fetch_array ($result)) {
			if (strpos ($display_field, ":")) {
				$displayed = ""; // set as null
				$split_display_field = explode (":", $display_field);
				for ($sl=0; $sl<sizeof($split_display_field); $sl++) {
					$displayed .= $r[$split_display_field[$sl]];
					// If not the last, insert separator
					if ($sl < (sizeof ($split_display_field) - 1))
						$displayed .= ", "; 
				}
			} else { // if it is only one field
				$displayed = $r[$display_field];
			} // end if-else displayed loop
		$id = $r["id"];
		if ($debug) $debuginfo = " [$id] ";
		$buffer .= "
		<OPTION VALUE=\"".prepare($id)."\" ".
		( (fm_value_in_string ($blob_data, $id)) ? "SELECTED" : "" ).
		">$displayed $debuginfo
		";
	} // end while
	$buffer .= " </SELECT>\n"; // end the select tag
	return $buffer;
} // end function freemed_multiple_choice

// function freemed_open_db
function freemed_open_db ($my_cookie) {

	global $display_buffer;

	// Verify
	if (!freemed_verify_auth()) {
		$display_buffer .= "<!-- -->
      <CENTER>
      <B>
      <P>
      "._("You have entered an incorrect username or password.")."
      <BR><BR>
      <I>"._("It is possible that your cookies have expired.")."</I>
      <P>
      </B>
      <A HREF=\"index.php\">"._("Return to the Login Screen")."</A>
      </CENTER>
		";
		template_display();
	} // end if connected loop
} // end function freemed_open_db

// function freemed_patient_box
//   general purpose patient link/info box
function freemed_patient_box ($patient_object) {
	// empty buffer
	$buffer = "";

	// top of box
	$buffer .= "
    <CENTER>
    <TABLE BORDER=0 CELLSPACING=0 CELLPADDING=5 WIDTH=\"100%\">
     <TR BGCOLOR=\"#000000\"><TD VALIGN=CENTER ALIGN=LEFT>
      <A HREF=\"manage.php?id=".urlencode($patient_object->id)."\"
      ><FONT COLOR=\"#ffffff\" SIZE=\"+1\">".
       $patient_object->fullName().
      "</FONT></A>
     </TD><TD ALIGN=CENTER VALIGN=CENTER>
      <FONT COLOR=\"#ffffff\">
      ".( (!empty($patient_object->local_record["ptid"])) ?
          $patient_object->idNumber() : "(no id)" )."
      </FONT>
     </TD><TD ALIGN=CENTER VALIGN=CENTER>
      <FONT COLOR=\"#ffffff\">
      &nbsp;
      <!-- ICON BAR NEEDS TO GO HERE ... TODO -->
      </FONT>
     </TD><TD VALIGN=CENTER ALIGN=RIGHT>
      <FONT COLOR=\"#cccccc\">
       ".$patient_object->age()." old, DOB ".
        $patient_object->dateOfBirth()."
      </FONT>
     </TD></TR>
    </TABLE>
    </CENTER>
	";
  
	// return buffer
	return $buffer;
} // end function freemed_patient_box

// function freemed_search_query()
//   generates result from in-notebook query
function freemed_search_query ($ctrl_array, $ord_array, 
                           $db = "", $id_var = "id")
{
  global $db_name, $sql;
  if (strlen($id_var)<1) { } else {
    if (is_array($id_var)) {
      while (list($k,$v) = each($id_var)) {
        global $$v;
        $_id_[]=$$v;
      }
    } else {
      global $$id_var;
      $_id_=$$id_var;
    } // import the id-list
  } // if id-vars exist
  
  $buf = "";
  if (strlen($db)<1) $db=$db_name;
  
  $buf .= "SELECT * FROM ".addslashes($db)." WHERE (1>0) ";
  
  $first_field=true;
  if (is_array($_id_)) { // array case
    $buf .= "AND (";
    while (list($k,$v) = each($_id_)) {
      if ($first_field) {
        $first_field = false;
      } else {
        if (strlen($v)>0) $buf .= " OR ";
      } // end checking for first field
      if (strlen($v)>0) $buf .= "(id = '".$v."')";
    } // while each id to check
    $buf .= ")";
  } else if (strlen($_id_>0)) { // end array case
    $buf .= " AND (id = '".$_id_."') ";
  } else { // non-array case
    $has_id = false;
  } // end non-id case
 
  $kill_it=true;
  $ctrl_array[""]="swedishchefborkborkbork"; // kludge to avoid 'Warning:'
  while (list($k,$v) = each($ctrl_array)) {
    if (strlen($k)>0) $kill_it=false;
  }
  if ($kill_it) unset($ctrl_array);
  else if (!$has_id) $buf .= "AND (";
 
  if (is_array($ctrl_array) AND count($ctrl_array)>0 AND strlen($_id_)>1) 
    $buf .= " OR (";

  $first_field = true;
  if (is_array($ctrl_array)) {
    reset($ctrl_array);
    while ( is_array($ctrl_array) AND 
           (list($fieldval, $fieldname) = each($ctrl_array)) ) { // the rest
      if (strlen($fieldval)>0) { // if it's an activated query
        if ($first_field) {
          $first_field = false;
        } else {
          $buf .= " AND ";
        }
        $buf .= " (
          $fieldname LIKE '%$fieldval%' OR
          $fieldname LIKE '$fieldval%' OR
          $fieldname LIKE '%$fieldval' OR
          $fieldname = '%$fieldval%'
        ) ";
      } // checking for active query
    } // while each in ctrl_array
    $buf .= " ) ";
  } // control array in place

  // KLUDGE TO KEEP FROM LISTING EVERYTHING
  if (!is_array($ctrl_array) AND (!$has_id)) {
    $buf .= " AND (1<0) ";
  } // we don't want to list *all* of them, now do we...

  if (count($ord_array)>0) { 
    $buf .= " ORDER BY ";
    $buf .= implode(',', $ord_array);
  } // include orderby clause
  return $sql->query($buf); 
} // end function freemed_search_query

// function freemed_specialty_display
//   displays specialty db entry in text format
function freemed_specialty_display ($param)
{
  if ((strlen($param)<1) OR ($param=="0")) {
    echo _("NONE SELECTED")."\n";
  } else {
    $query = "SELECT * FROM specialty ".
       "WHERE id='$param'";
    $result = $sql->query ($query);
    if (!$result) {
      echo "$NO_RESULT_OF_QUERY";
      // don't do anything...! 
    } else { // exit if no more
      $row=$sql->fetch_array($result); 
      echo prepare ($row[specdegree]." (".
                    $row[specname].")");
    }
  }
} // end function freemed_specialty_display


  // USER AUTHENTICATION FUNCTIONS (19990701)

  // these are from px.skylar.com, and not mine
  // but they are heavily modified to work with
  // a root "backdoor", so as to allow for setup...

function freemed_auth_db_connect () {
  global $Connection, $sql; // , $db_user, $db_password;
  // $Connection = $sql->connect (DB_HOST, DB_USER, DB_PASSWORD);
  return $sql;
} // end function freemed_auth_db_connect

// freemed_verify_auth:
//   moved to sessions support as of version 0.3
function freemed_verify_auth ( ) {
	global $debug, $Connection, $sql, $REMOTE_ADDR;
	global $PHP_SELF, $SESSION, $_username, $_password;

	// Do we have to check for _username?
	$check = !empty($_username);

	// Check for authdata array
	if (is_array($SESSION["authdata"])) {
		// Check to see if ipaddr is set or not...
		if (!SESSION_PROTECTION) {
			return true;
		} else {
			if ( isset($SESSION["ipaddr"]) and
				($SESSION["ipaddr"] == $REMOTE_ADDR) ) {
				// We're already authorized
				return true;
			} else {
				// IP address has changed, ERROR
				return false;
			} // end checking ipaddr
		} // end checking for SESSION_PROTECTION
	} elseif ($check) {
		// Quickly check for root un/pw pair (handle null pw)
		if ( ($_username=="root") and ($_password==DB_PASSWORD) ) {
			// Pass the proper session variable
			$SESSION["authdata"] = array (
				"username" => $_username,
				"user" => "1" // superuser id
			);
			// Set ipaddr for SESSION_PROTECTION
			$SESSION["ipaddr"] = $REMOTE_ADDR;
			// Return back that this is true
			return true;
		}

		// Find this user
  		$result = $sql->query ("SELECT * FROM user ".
			"WHERE id = '".addslashes($_username)."'");

		// If the user isn't found, false
		if (!$sql->results($result)) {
			return false;
		}

		// Get information
		$r = $sql->fetch_array ($result);

		// Check password
		if ($_password == $r["userpassword"]) {
			// Set session vars
			$SESSION["authdata"] = array (
				"username" => $_username,
				"user" => $r["id"]
			);
			// Set ipaddr for SESSION_PROTECTION
			$SESSION["ipaddr"] = $REMOTE_ADDR;

			// Authorize
			return true;
		} else { // check password
			// Failed password check
			unset ( $SESSION["authdata"] );
			unset ( $SESSION["ipaddr"] );
			return false;
		} // end check password
	} // end of checking for authdata array
} // end function freemed_verify_auth

 #
 # FREEMED DB WRAPPERS SECTION
 #   Added 19990714 -- allows use of other databases besides MySQL by
 #   making all database access the same, specified by a variable in
 #   lib/freemed.php...
 #
 # 19990722 - Merged functions from Gianugo Rabellino's SQL
 #            Abstraction Library (SAL) from px.skylar.com
 #            and added msql compatibility

function fdb_affected_rows ($result = "0") {
  switch (strtolower(DB_ENGINE)) {
    case "odbc": return odbc_num_rows ($result); break;
    case "postgres": return pg_NumRows (); break;
    case "mysql":
    default: return mysql_affected_rows (); break;
  } // end switch
} // end function fdb_affected_rows

/*
function fdb_close ($null = "") {
  global $Connection;
  switch (strtolower(DB_ENGINE)) {
    case "odbc":     return odbc_close ($Connection); break;
    case "postgres": return pg_Close ($Connection);   break;
    case "msql":     return msql_close ();            break;
    case "mysql":
    default:         return mysql_close ();           break;
  } // end switch
} // end function fdb_close

function fdb_connect ($db_host, $user, $password) {
  global $database;
  switch (strtolower(DB_ENGINE)) {
    case "odbc": return odbc_pconnect ($host, $user, $password); break;
    case "postgres": return pg_Connect ("dbname=$database".
                                        "host=".DB_HOST." ".
                                        "user=".DB_USER." ".
                                        "password=".DB_PASSWORD);break;
    case "msql":
      $Connection = msql_pconnect (DB_HOST);
      if ($Connection) msql_select_db ($database, $Connection);
      return $Connection;
      break;
    case "mysql":
    default:
      $Connection = mysql_pconnect (DB_HOST, DB_USER, DB_PASSWORD);
      if ($Connection) mysql_select_db ($database, $Connection);
      return $Connection;
      break;
  } // end switch
} // end function fdb_connect

function fdb_create_db ($created_name) {
  global $Connection;
  switch (strtolower(DB_ENGINE)) {
    case "odbc": return odbc_exec ($Connection, "CREATE DATABASE ".
      "$created_name"); break;
    case "postgres": return pg_Exec ($Connection, "CREATE DATABASE ".
      "$created_name"); break;
    case "mysql":
    default: return mysql_create_db ($created_name); break;
  } // end switch
} // end function fdb_create_db

function fdb_drop_db ($dropped_name) {
  global $Connection;
  switch (strtolower(DB_ENGINE)) {
    case "odbc": return odbc_exec ($Connection, "DROP DATABASE ".
      "$dropped_name"); break;
    case "postgres": return pg_Exec ($Connection, "DROP DATABASE ".
      "$dropped_name"); break;
    case "mysql":
    default: return mysql_drop_db ($dropped_name); break;
  } // end switch
} // end function fdb_drop_db

function fdb_fetch_array ($result) {
  global $Connection;
  if ($result<1) return array();
  switch (strtolower(DB_ENGINE)) {
    case "odbc": 
      // thanks to Giannugo Rabellino <nemorino@opera.it>'s
      // SQL Abstraction library for this one...
      $row = array (); $res_array = array ();
      $res_array = odbc_fetch_row ($result, $Connection);
      $nf = count($result+2); // field numbering starts at 1
      for ($count=1; $count<$nf; $count++) {
        $field_name  = odbc_field_name ($result, $count);
        $field_value = odbc_result ($result, $field_name);
        $row[$field_name] = $field_value;
      } // end for loop
      return $row; // send back jimmie-rigged row array...
      break;
    case "postgres": return pg_Fetch_Array    ($result); break;
    case "msql":     return msql_fetch_array  ($result); break;
    case "mysql": 
    default:         return mysql_fetch_array ($result); break;
  } // end switch
} // end function fdb_fetch_array

function fdb_last_record ($last_result="") {
  global $Connection, $result;
  if ($last_result=="") $last_result = $result;
  switch (strtolower(DB_ENGINE)) {
    case "msql":    return msql_insert_id ();                      break;
    case "odbc":    DIE("fdb_last_record:: ODBC not implemented"); break;
      //return fdb_num_rows (
      //        fdb_query ("SELECT * FROM $tablename") );  break;
      
    case "postgres":               return pg_getlastoid ($result); break;
    case "mysql"   :
    default:
      return mysql_insert_id ();
      break;
  } // end switch
} // end function fdb_last_record

function fdb_num_rows ($result) {
  switch (strtolower(DB_ENGINE)) {
    case "odbc":     return odbc_num_rows  ($result);  break;
    case "postgres": return pg_NumRows     ($result);  break;
    case "msql":     return msql_num_rows  ($result);  break;
    case "mysql":
    default:         return mysql_num_rows ($result);  break;
  } // end switch
} // end function fdb_num_rows

function fdb_query ($querystring) {
  global $Connection, $database;
  switch (strtolower(DB_ENGINE)) {
    case "odbc": 
      return odbc_execute ( odbc_prepare ($Connection, $querystring) );
      break;
    case "postgres": 
      return pg_exec ($Connection, $querystring); break;
    case "msql":
      return msql_query ($querystring, $Connection);
      break;
    case "mysql": 
    default:
      return mysql_query ($querystring); break;
  } // end switch
} // end function fdb_query

*/

  //
  //  FUNCTIONS FOR DEALING WITH MISCELLANEOUS STUFF
  //  (19990722)
  //

function fm_date_assemble ($datevarname="", $array_index=-1) {
  if ($datevarname=="") return ""; // return nothing if no variable is given
  global ${$datevarname."_m"}, ${$datevarname."_d"}, ${$datevarname."_y"};
  if ($array_index == -1) {
    $m = ${$datevarname."_m"};
    $d = ${$datevarname."_d"};
    $y = ${$datevarname."_y"};
  } else {
    $m = ${$datevarname."_m"}[$array_index];
    $d = ${$datevarname."_d"}[$array_index];
    $y = ${$datevarname."_y"}[$array_index];
  } // end checking for array index
  return $y."-".$m."-".$d;                     // return SQL format date
} // end function fm_date_assemble

function fm_date_entry ($datevarname="", $pre_epoch=false, $arrayvalue=-1) {
  if ($datevarname=="") return false;  // indicate problems
  if (($arrayvalue+0)==-1) { $suffix="";        
                             $pos="";                    }
  else                     { $suffix="[]";
                             $pos="[$arrayvalue]"; }
  global $$datevarname, ${$datevarname."_y"}, 
    ${$datevarname."_m"}, ${$datevarname."_d"};
  $months = array ("", "Jan", "Feb", "Mar", "Apr", "May", "Jun",
                       "Jul", "Aug", "Sep", "Oct", "Nov", "Dec");
  $w = ${"$datevarname$pos"};
  $m = ${$datevarname."_m".$pos};
  $d = ${$datevarname."_d".$pos};
  $y = ${$datevarname."_y".$pos};
  if (!empty($w)) {
    // if date is not empty, split into $m, $d, $y
    $y = substr ($w, 0, 4);  // split year
    $m = substr ($w, 5, 2);  // split month
    $d = substr ($w, 8, 2);  // split day
  } elseif (empty($y) and empty($m) and empty($d)) {
    $y = date ("Y")+0;
    $m = date ("m")+0;
    $d = date ("d")+0;
  } // end if not empty whole date

  switch ($pre_epoch) {
    case true:
     $starting_year = (date("Y")-120);
     $ending_year   = (date("Y")+20);
     break;
    case false: default:
     $starting_year = (date("Y")-10);
     $ending_year   = (date("Y")+20);
     break;
  } // end switch for pre_epoch

  // just in case, for legacy dates...
  if (($y>1800) AND ($y<$starting_year)) $starting_year = $y;
  if (($y>1800) AND ($y>$ending_year))   $ending_year   = $y;

  // form the buffers, then assemble
  $buffer_m = "
    <SELECT NAME=\"".$datevarname."_m$suffix\">
     <OPTION VALUE=\"00\" ".
     ( ($m==0) ? "SELECTED" : "" ).">"._("NONE")."\n";
  for ($i=1;$i<=12;$i++) {
   $buffer_m .= "\n<OPTION VALUE=\"".( ($i<10) ? "0" : "" )."$i\" ".
     ( ($i==$m) ? "SELECTED" : "" ).">"._($months[$i])."\n";
  } // end for loop (months) 
  $buffer_m .= "   </SELECT>\n";

  $buffer_d = "
   <SELECT NAME=\"".$datevarname."_d$suffix\">
    <OPTION VALUE=\"00\" ".
    ( ($d==0) ? "SELECTED" : "" ).">NONE
  ";
  for ($i=1;$i<=31;$i++) {
   $buffer_d .= "\n<OPTION VALUE=\"".( ($i<10) ? "0" : "" )."$i\" ".
     ( ($i==$d) ? "SELECTED" : "" ).">$i\n";
  } // end looping for days
  $buffer_d .= "    </SELECT>\n";

  $buffer_y = "
   <SELECT NAME=\"".$datevarname."_y$suffix\">
    <OPTION VALUE=\"0000\" ".
    ( ($y==0) ? "SELECTED" : "" ).">NONE
  ";
  for ($i=$starting_year;$i<=$ending_year;$i++) {
   if ($i==$y) { $this_selected="SELECTED"; }
    else       { $this_selected="";         }
   $buffer_y .= "\n<OPTION VALUE=\"$i\" ".
     ( ($i==$y) ? "SELECTED" : "" ).">$i\n";
  } // end for look (years)
  $buffer_y .= "    </SELECT>\n";

  // now actually display the input boxes
  switch (freemed_config_value("dtfmt")) {
    case "mdy":
      return $buffer_m . " <B>-</B> ".
             $buffer_d . " <B>-</B> ".
	     $buffer_y;
      break;
    case "dmy":
      return $buffer_d . " <B>-</B> ".
             $buffer_m . " <B>-</B> ".
	     $buffer_y;
      break;
    case "ymd": default:
      return $buffer_y . " <B>-</B> ".
             $buffer_m . " <B>-</B> ".
	     $buffer_d;
      break;
  } // end switch for dtfmt config value
} // end function fm_date_entry

function fm_date_print ($actualdate, $show_text_days=false) {
  global $lang_months, $lang_days;
  $y  = substr ($actualdate, 0, 4);        // extract year
  $m  = substr ($actualdate, 5, 2);        // extract month
  $d  = substr ($actualdate, 8, 2);        // extract day
  $ts = mktime (0, 0, 0, $m, $d, $y);      // generate timestamp

  $mt = $lang_months[($m+0)];              // month           (text)
  $wt = $lang_days[1 + (date("w", $ts))];  // day of the week (text)
  // decide if we show the week days names...
  if ($show_text_days) { $week = $wt.", "; }
   else                { $week = " ";      }
  switch (freemed_config_value("dtfmt")) {
    case "mdy":           return chop($week.$mt." ".$d.", ".$y); break;
    case "dmy":           return chop($week.$d." ".$mt.", ".$y); break;
    case "ymd": default:  return chop($y."-".$m."-".$d);         break; 
  } // end switch
} // end function fm_date_print

function fm_htmlize_array ($variable_name, $cur_array) {
  $array_length = count ($cur_array);
  if ($array_length==0)
  {
      return $buffer;  // if nothing, false!
  }
  //$buffer = "";
  //if ($array_length==1) {
  //  $buffer .= " <INPUT TYPE=HIDDEN NAME=\"$variable_name\"
  //     VALUE=\"".prepare($cur_array)."\">
  //   ";
  //  return $buffer;                       // it printed, return true...
  //} // end of array length = 1
  for ($i=0;$i<$array_length;$i++)
    $buffer .= "
      <INPUT TYPE=HIDDEN NAME=\"$variable_name"."[$i]\"
       VALUE=\"".prepare($cur_array[$i])."\">
     ";
  return $buffer;                         // be nice, return true
} // end function fm_htmlize_array

function fm_make_string_array($string)
{
	// ensure string ends in :
	if (!strpos($string,":"))
		return $string.":";
	return $string;

}
function fm_join_from_array ($cur_array) {
  if (count($cur_array)==0) return ""; // error checking
  if (!is_array($cur_array)) return "$cur_array";  // error checking
  return implode ($cur_array, ":");
} // end function fm_join_from_array 

function fm_number_select ($varname, $min=0, $max=10, $step=1, $addz=false) {
  global $$varname; // bring in the variable
  $selected = $$varname; // get selected..?
  $buffer = "";
  $buffer .= "\n<SELECT NAME=\"".prepare($varname)."\">\n";
  if                   ($step==0)    $step = 1;    // bounds checking
  if ( ($min>$max) AND ($step>=0) )  return false; // bounds checking
  if ( ($min<$max) AND ($step<=0) )  return false; // bounds checking
  for ($i=$min;$i<=$max;$i+=$step) {
    $buffer .=  "<OPTION VALUE=\"$i\"".
      ( (("$selected"=="$i") or ($selected==$i)) ? "SELECTED" : "" ).
      ">".( (($i<10) and ($addz)) ? "0" : "" )."$i\n";
  } // end for loop
  $buffer .= "</SELECT>\n"; // end select tag
  return $buffer;
} // end function fm_number_select

function fm_phone_assemble ($phonevarname="", $array_index=-1) {
  $buffer = ""; // we use buffered output for notebook class!
  if ($phonevarname=="") return ""; // return nothing if no variable is given
  global $$phonevarname, ${$phonevarname."_1"},
    ${$phonevarname."_2"}, ${$phonevarname."_3"}, 
    ${$phonevarname."_4"}, ${$phonevarname."_5"};
  if ($array_index == -1) {
    $w  = $$phonevarname;    // whole number
    $p1 = ${$phonevarname."_1"};    // part 1
    $p2 = ${$phonevarname."_2"};    // part 2
    $p3 = ${$phonevarname."_3"};    // part 3
    $p4 = ${$phonevarname."_4"};    // part 4
    $p5 = ${$phonevarname."_5"};    // part 5
  } else {
    $w  = $$phonevarname[$array_index];  // whole number
    $p1 = ${$phonevarname."_1"}[$array_index];  // part 1
    $p2 = ${$phonevarname."_2"}[$array_index];  // part 2
    $p3 = ${$phonevarname."_3"}[$array_index];  // part 3
    $p4 = ${$phonevarname."_4"}[$array_index];  // part 4
    $p5 = ${$phonevarname."_5"}[$array_index];  // part 5
  } // end checking for array index
  switch (freemed_config_value("phofmt")) {
    case "usa":
     return $p1.$p2.$p3.$p4;        // assemble number and put it all together
    case "fr":
     return $p1.$p2.$p3.$p4.$p5;    // assemble number and put it all together
    case "unformatted":
    default:
     return $w;                     // return whole number...
  } // end switch for formatting
} // end function fm_phone_assemble

function fm_phone_entry ($phonevarname="", $array_index=-1) {
  if ($phonevarname=="") return false;  // indicate problems
  if (($array_index+0)==-1) { $suffix="";   }     
  else                     { $suffix="[]"; }
  $formatting = freemed_config_value("phofmt"); // get phone formatting
  global $$phonevarname, ${$phonevarname."_1"},	 // get global vars
         ${$phonevarname."_2"}, ${$phonevarname."_3"}, 
         ${$phonevarname."_4"}, ${$phonevarname."_5"}; 

  if ($array_index == -1)  {
    $w = ${$phonevarname};    // whole number
  } else {
    $w = ${$phonevarname}[$array_index];  // whole number
  }

  if (!empty($w)) {
    // if phone # is not empty, split
    switch ($formatting) {
      case "usa":
       $p1 = substr($w,  0, 3); // area code
       $p2 = substr($w,  3, 3); // prefix
       $p3 = substr($w,  6, 4); // local number
       $p4 = substr($w, 10, 4); // extention
       break;
      case "fr":
       $p1 = substr($w, 0, 2); 
       $p2 = substr($w, 2, 2); 
       $p3 = substr($w, 4, 2); 
       $p4 = substr($w, 6, 2); 
       $p5 = substr($w, 8, 2); 
       break;
      case "unformatted":
      default:
       // nothing!! hahahahahahahahahahahahaha!
       break;
    } // end formatting case statement
  } else { // end if not empty whole date
    if ($array_index == -1) {
      $p1 = ${$phonevarname."_1"};    // part 1
    $p2 = ${$phonevarname."_2"};    // part 2
    $p3 = ${$phonevarname."_3"};    // part 3
    $p4 = ${$phonevarname."_4"};    // part 4
    $p5 = ${$phonevarname."_5"};    // part 5
    } else {
    $p1 = ${$phonevarname."_1"}[$array_index];  // part 1
    $p2 = ${$phonevarname."_2"}[$array_index];  // part 2
    $p3 = ${$phonevarname."_3"}[$array_index];  // part 3
    $p4 = ${$phonevarname."_4"}[$array_index];  // part 4
    $p5 = ${$phonevarname."_5"}[$array_index];  // part 5
    } // end checking for array index
  }

  // now actually display the input boxes
  switch ($formatting) {
    case "usa":
     $buffer .= "
      <B>(</B>
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_1$suffix\" SIZE=4
       MAXLENGTH=3 VALUE=\"$p1\"> <B>)</B>
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_2$suffix\" SIZE=4
       MAXLENGTH=3 VALUE=\"$p2\"> <B>-</B>
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_3$suffix\" SIZE=5
       MAXLENGTH=4 VALUE=\"$p3\"> <I>ext.</I>
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_4$suffix\" SIZE=5
       MAXLENGTH=4 VALUE=\"$p4\">
     "; break;
    case "fr":
     $buffer .= "
      <B>(</B>
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_1$suffix\" SIZE=3
       MAXLENGTH=2 VALUE=\"$p1\"> <B>)</B>
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_2$suffix\" SIZE=3
       MAXLENGTH=2 VALUE=\"$p2\"> 
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_3$suffix\" SIZE=3
       MAXLENGTH=2 VALUE=\"$p3\">
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_4$suffix\" SIZE=3
       MAXLENGTH=2 VALUE=\"$p4\">
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."_5$suffix\" SIZE=3
       MAXLENGTH=2 VALUE=\"$p4\">
     "; break;
    case "unformatted": 
    default:
     $buffer .= "
      <INPUT TYPE=TEXT NAME=\"".$phonevarname."$suffix\" SIZE=15
       MAXLENGTH=16 VALUE=\"$w\">
     "; break;
  } // end switch for dtfmt config value

  return $buffer;                         // we exited well!
} // end function fm_phone_entry

function fm_split_into_array ($original_string) {
  return explode (":", $original_string);
} // end function fm_split_into_array

function fm_value_in_array ($cur_array, $value) {
  if (count ($cur_array) < 0) return false; // avoid errors (from old code)
  //if (!is_array ($cur_array)) return ($cur_array == $value);
  $found = false; // initially presume that it is not there
  for ($c=0;$c<count($cur_array);$c++) // loop through array
    if ($cur_array[$c]==$value)        // if there is a match...
      $found = true;                   // return true.
  return $found; // send value back to program
} // end function fm_split_into_array

function fm_value_in_string ($cur_string, $value) {

  if ( strpos ($cur_string, ":") > 0 )
	{
    	$this_array = fm_split_into_array ($cur_string);
  		return fm_value_in_array ($this_array, $value);
	}

	if (strstr($cur_string,$value) != "")
		return true;
	return false;
  
} // end function fm_value_in_string

function fm_button ($b_text) {
  //$enc_b_text = urlencode ($b_text);
  // echo "<IMG SRC=\"generate_button.php3?".urlencode($enc_b_text)."\"".
  //     " ALT=\"".htmlentities($b_text)."\">";
  // 19990921 -- try this instead:
  echo "
    <TABLE CELLPADDING=1 CELLSPACING=0 BORDER=0 ALIGN=CENTER VALIGN=CENTER
     ><TR><TD BGCOLOR=#000000><FONT COLOR=#cccccc FACE=\"Arial, Verdana\"
     SIZE=-1><B>$b_text</B></FONT></TD></TR></TABLE>&nbsp;
  "; 
}

// fm_eval -- evaluate string variables (with security checks, of course)
function fm_eval ($orig_string) {
  // import all global variables
  // (thanks to jb@as220.org for this one)
  reset ($GLOBALS);
  //for ($i=0;$i<100;$i++) next ($GLOBALS); // skip the drek
  while (list($k,$v) = each ($GLOBALS))
   if (!isset($$k)) $$k = $GLOBALS[$k];        // $$k = $GLOBALS[$k];
  $loc_string = $orig_string;                  // transfer to internal var
  $sec_string = fm_secure ($loc_string);       // secure the string
  eval ("\$new_string = \"$sec_string\";");    // evaluate
  while (list($k,$v) = each ($GLOBALS))
   if ($k != "GLOBALS") unset ($$k);           // destroy the variables
  return $new_string;                          // return
} // end function fm_eval

// fm_secure -- secures strings that are to be evaled by simply removing
//              all secure varaibles...
function fm_secure ($orig_string) {
  $this_string = "$orig_string";  // pass to internal variable
  $this_string = str_replace ("\$db_user",     "", $this_string);
  $this_string = str_replace ("\$db_password", "", $this_string);
  $this_string = str_replace ("\$db_host",     "", $this_string);
  $this_string = str_replace ("\$database",    "", $this_string);
  $this_string = str_replace ("\$gifhome",     "", $this_string);
  $this_string = str_replace ("\$db_engine",   "", $this_string);
  return $this_string;    // return cleansed string
} // end function fm_secure


function fm_get_active_coverage ($ptid=0)
{
        global $database, $sql, $cur_date;
        $result = 0;
	if ($ptid == 0)
           return $result;
        $query = "SELECT id FROM coverage WHERE ";
        $query .= "covpatient='$ptid' AND covstatus='".ACTIVE."' ";
        $result = $sql->query($query);
        if (!$result)
           return $result;  // not an array!
        $sub=0;
        while ($rec = $sql->fetch_array($result))
        {
            $ins_id[$sub] = $rec["id"];
            $sub++;
        }
		if ($sub == 0)
			return 0;
        return $ins_id;

} // end get active coverages

function fm_verify_patient_coverage($ptid=0,$coveragetype=PRIMARY)
{
        global $database, $sql, $cur_date;
        $result = 0;
		if ($ptid == 0)
           return $result;
	
		// default coveragetype is primary	

        $query = "SELECT id FROM coverage WHERE ";
        $query .= "covpatient='$ptid' AND covstatus='".ACTIVE."' AND covtype='$coveragetype' ";
        $result = $sql->query($query);
		if (!$result)
			return 0;
		if (!$sql->num_rows($result))
			return 0;
		$row = $sql->fetch_array($result);
		$ret = $row[id];
		return $ret;
}

// function freemed_display_selectbox_array
function freemed_display_selectbox_array ($result, $format, $name="", $param="")
{
  global $$param; // so it knows to put SELECTED on properly
  static $var; // array of $result-IDs so we only go through them once
  static $count; // count of results
  global $sql; // for database connection

  if (!isset($var["$result"])) {
    if ($result) {
      $count["$result"] = $sql->num_rows($result);
      while ($var["$result"][] = $sql->fetch_array($result));
    } // non-empty result
  } // if we haven't gone through this list yet
 
  $buffer = "";
  if ($count["$result"]<1) { 
    $buffer .= _("NONE")."
      <INPUT TYPE=HIDDEN NAME=\"$name\" VALUE=\"0\">";
    return $buffer; // do nothing!
  } // if no result

  $buffer .= "
    <SELECT NAME=\"$name\">
      <OPTION VALUE=\"0\">"._("NONE SELECTED")."
  ";
  reset($var["$result"]); // if we're caching it, we have to reset it!
  while ( (list($pickle,$item) = each($var["$result"])) 
                                     AND ($item[id])) { // no null values!
    // START FORMAT-FETCHING
    $format_array = explode("#",$format); // odd members are variable names!
    while (list($index,$str) = each($format_array)) {
      if ( !($index & 1) ) continue; // ignore the evens!
      $format_array[$index] = $item[$str];// can't just change $str!
    } // while replacing each variable name
    $this_format = join("", $format_array); // put it back together
    // END FORMAT-FETCHING    
    $buffer .= "
      <OPTION VALUE=\"$item[id]\" ".
      ( ($item[id] == $$param) ? "SELECTED" : "" ).
      ">".prepare($this_format)."\n";
  } // while fetching result
  $buffer .= "
    </SELECT>
  ";
  
  return $buffer;
} // end function freemed_display_selectbox_array


function fm_time_entry ($timevarname="") {
  if ($timevarname=="") return false;  // indicate problems
  global $$timevarname, ${$timevarname."_h"}, 
    ${$timevarname."_m"}, ${$timevarname."_ap"};


  $w = $$timevarname;       
  $h = ${$timevarname."_h"};
  if (!empty($w))
  {
		// if timeval then extract the pieces
		// this could be first time thru since $timevarname
        // will not be saved across page invocations
	  $values = explode(":",$$timevarname);
      ${$timevarname."_h"}  = $values[0];
      ${$timevarname."_m"}  = $values[1];
      ${$timevarname."_ap"} = $values[2];
      $ap = $values[2];
     
  }
  elseif (empty($h))
  {
	  // if not timeval and not hour then
      // plug a default. we shoud have a value in $h
      // secondtime thru
	  $$timevarname = "00:00:AM";
      ${$timevarname."_h"} = "00";
	  ${$timevarname."_m"} = "00";
	  ${$timevarname."_ap"} = _("AM");
	  $ap = _("AM");
  }

  //echo ${$timevarname."_h"}."<BR>";
  //echo ${$timevarname."_m"}."<BR>";
  //echo ${$timevarname."_ap"}."<BR>";
	

  $buffer_h = fm_number_select($timevarname."_h",0,12);
  $buffer_m = fm_number_select($timevarname."_m",0,59);
  $buffer_ap = "<SELECT NAME=\"$timevarname"."_ap"."\">".
	"<OPTION VALUE=\"AM\" ".
		( $ap=="AM" ? "SELECTED" : "").">". _("AM").
	"<OPTION VALUE=\"PM\" ".
		( $ap=="PM" ? "SELECTED" : "").">". _("PM");
   
  return $buffer_h.$buffer_m.$buffer_ap;
  
} // end fm_time_entry


function fm_time_assemble ($timevarname="") {
  if ($timevarname=="") return ""; // return nothing if no variable is given
  global ${$timevarname."_h"}, ${$timevarname."_m"}, ${$timevarname."_ap"};

    $m = ${$timevarname."_m"};
    $h = ${$timevarname."_h"};
    $ap = ${$timevarname."_ap"};
  return $h.":".$m.":".$ap;                     // return SQL format date
} // end function fm_time_assemble

//--------------------------------------------------------------------------
//--------------------------------------------------------------------------
//--------------------------------------------------------------------------

//*********************  TEMPLATE SUPPORT

function template_display ($terminate_on_execute=true) {
	global $display_buffer; // localize display buffer
	global $template; // localize template
	foreach ($GLOBALS AS $k => $v) global $$k;

	if (file_exists("lib/template/".$template."/template.php")) {
		include_once ("lib/template/".$template."/template.php");
	} else { // otherwise load the default template
		include_once ("lib/template/default/template.php");
	} // end template load

	// Kill everything after this has been displayed
	if ($terminate_on_execute) die("");
} // end function template_display

//********************** END TEMPLATE SUPPORT

function page_push () {
	global $SESSION, $PHP_SELF, $page_title;

	// Import it if it exists
	if (isset($SESSION["page_history"])) {
		// Import
		$page_history = $SESSION["page_history"];

		// Check to see if this is the last item on the list...
		// ... kick out without adding.
		if (basename($page_history[(count($page_history))]) ==
			basename($PHP_SELF)) return true;
	} // end checking for existing history

	// Add to the list of pages
	$page_history["$page_title"] = basename($PHP_SELF);

	// Reimport into SESSION
	$SESSION["page_history"] = $page_history;
} // end function page_push

function page_pop () {
	global $SESSION;

	// Return false if there is nothing in the list
	if (!isset($SESSION["page_history"])) return false;

	// Import page_history
	$page_history = $SESSION["page_history"];

	// Otherwise get the last one and return it ...
	$to_return = $page_history[(count($page_history)-1)];
	$to_return_name = $page_history[(count($page_history_name)-1)];

	// .. then remove it from the stack
	unset($page_history[(count($page_history)-1)]);
	unset($page_history_name[(count($page_history)-1)]);

	// Reimport into SESSION
	$SESSION["page_history"] = $page_history;
	$SESSION["page_history_name"] = $page_history_name;

	// And return value (access as list(x,y) = page_pop())
	return array ($to_return, $to_return_name);
} // end function page_pop

function patient_push ($patient) {
	global $SESSION;

	// Import it if it exists
	if (isset($SESSION["patient_history"])) {
		// Import
		$patient_history = $SESSION["patient_history"];

		// Clean out null entries...
		foreach ($patient_history AS $k => $v) {
			if (!$v) unset($patient_history[$k]);
		} // end foreach

		// Check to see if this is the last item on the list...
		// ... kick out without adding.
		if ($patient_history[(count($patient_history))] == $patient) {
			// Reimport due to cleaning
			$SESSION["patient_history"] = $patient_history;

			// And we don't have to add it, exit with true
			return true;
		} // end checking if we just saw them...
	} // end checking for existing history

	// Add to the list of pages
	$patient_history[] = $patient;

	// Reimport into SESSION
	$SESSION["patient_history"] = $patient_history;
} // end function patient_push

/*------------------- DON'T THINK THIS IS NEEDED---------------------------
function patient_pop () {
	global $SESSION;

	// Return false if there is nothing in the list
	if (!isset($SESSION["patient_history"])) return false;

	// Import patient_history
	$patient_history = $SESSION["patient_history"];

	// Otherwise get the last one and return it ...
	$to_return = $patient_history[(count($patient_history)-1)];

	// .. then remove it from the stack
	unset($patient_history[(count($patient_history)-1)]);

	// Reimport into SESSION
	$SESSION["patient_history"] = $patient_history;

	// And return value
	return $to_return;
} // end function patient_pop
-------------------------------- end of unneeded function */

function patient_history_list () {
	global $SESSION;

	// Return false if there is nothing in the list
	if (!isset($SESSION["patient_history"])) return false;

	// Import patient_history
	$patient_history = $SESSION["patient_history"];

	// Check for no patient history
	if (count($patient_history)<1) return false;

	// Create new empty array
	unset($history);

	// Loop through array
	foreach ($patient_history AS $k => $v) {
		// Get patient information
		$this_patient = new Patient ($v);
	
		// Form Lastname, Firstname, ID list item
		$key = $this_patient->fullName() . " (".$v.")";

		// Add to new array
		$history["$key"] = $v;
	} // end foreach

	// Return generated array
	return array_reverse($history);
} // end function patient_history_list

function page_history_list () {
	global $SESSION;

	// Return false if there is nothing in the list
	if (!isset($SESSION["page_history"])) return false;

	// Import patient_history
	$page_history = $SESSION["page_history"];

	// Check for no patient history
	if (count($page_history)<1) return false;

	// Create new empty array
	unset($history);

	// Loop through array
	foreach ($page_history AS $k => $v) {
		if (!empty($k) and !empty($v)) {
			// Add to new array
			$history["$k"] = $v;
		}
	} // end foreach

	// Return generated array
	return array_reverse($history);
} // end function page_history_list

} // end checking for __API_PHP__

?>
