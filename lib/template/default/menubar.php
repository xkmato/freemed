<?php
 // $Id$
 // $Author$

// Check for presence of patient and pagehistories
$patient_history = patient_history_list();
$page_history = page_history_list();
if ($patient_history or $page_history) {
	print "
	<TABLE WIDTH=\"100%\" CELLSPACING=0 CELLPADDING=0 VALIGN=TOP
	 ALIGN=CENTER>
	";
}
if ($patient_history) {
	print "
	<TR><TD ALIGN=RIGHT>
	<FORM ACTION=\"manage.php\" METHOD=POST>
	".html_form::select_widget("id", $patient_history)."
	</TD><TD ALIGN=CENTER>
	<INPUT TYPE=IMAGE SRC=\"lib/template/default/magnifying_glass.".
	IMAGE_TYPE."\"
		WIDTH=\"16\" HEIGHT=\"16\" ALT=\"[Manage]\">
	</FORM>
	</TD></TR>
	";
} // end checking for patient history

if ($page_history) {
	// Set current page as default selection
	$location = basename($PHP_SELF);
	
	// Show the actual pick list
	print "
	<TR><TD ALIGN=RIGHT>
	<FORM ACTION=\"redirect.php\" METHOD=POST>
	".html_form::select_widget("location", $page_history)."
	</TD><TD ALIGN=CENTER>
	<INPUT TYPE=IMAGE SRC=\"lib/template/default/forward.".
	IMAGE_TYPE."\"
		WIDTH=\"16\" HEIGHT=\"16\" ALT=\"[Jump to page]\">
	</FORM>
	</TD></TR>
	";
} // end checking for page history

if ($patient_history or $page_history) {
	print "
	</TABLE>
	";
}

?>
<UL>
	<LI><A HREF="admin.php"><?php print _("Administration Menu"); ?></A>
	<LI><A HREF="billing_functions.php?patient=<?php print $SESSION["current_patient"]; ?>"
		><?php print _("Billing Functions"); ?></A>
	<LI><A HREF="db_maintenance.php"
		><?php print _("Database Maintenance"); ?></A>
	<LI><A HREF="patient.php"><?php print _("Patient Functions"); ?></A>
	<LI><A HREF="reports.php"><?php print _("Reports"); ?></A>
</UL>
<HR>
<UL>
<?php
//----- Check for help file link
if ( ($help_url = help_url()) != "help.php" ) print "\t<LI><A HREF=\"#\" ".
	"onClick=\"window.open('".$help_url."', 'Help', 'width=600,height=400,".
	"resizable=yes');\">"._("Help")."</A>\n";
?>
	<LI><A HREF="main.php"><?php print _("Return to Main Menu"); ?></A>
	<LI><A HREF="logout.php"><?php print _("Logout"); ?></A>
</UL>
<!-- new functions come *after* everything else -->
<?php 

//----- Check to see if a menubar array exists
if (is_array($menu_bar)) {
	print "<HR>\n\n";
	print "<UL>\n";
	foreach ($menu_bar AS $k => $v) {
		if ($v != NULL) {
		print "\t<LI><A HREF=\"".$v."\" ".
			"onMouseOver=\"window.status='".prepare(_($k))."'; ".
			"return true;\" ".
			"onMouseOut=\"window.status=''; return true;\">".
			prepare(_($k))."</A>\n";
		} // end checking for null
	} // end foreach
	print "</UL>\n";
} else { // if is array
	print "&nbsp;\n";
} // end if is array


?>
