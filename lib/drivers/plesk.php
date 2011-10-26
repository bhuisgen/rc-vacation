<?php

/*
 +-----------------------------------------------------------------------+
 | lib/drivers/plesk.php                                                 |
 |                                                                       |
 | Copyright (C) 2009 Boris HUISGEN <bhuisgen@hbis.fr>                   |
 | Copyright (C) 2011 Vicente MONROIG <vmonroig@digitaldisseny.com>      |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+

 +-----------------------------------------------------------------------+
   Please, note:
   Needs access for apache user in /etc/sudoers. For example:
   www-data ALL=NOPASSWD: /opt/psa/bin/autoresponder

   Recommended options in vacation config.inc.php:
   $rcmail_config['vacation_gui_vacationdate'] = FALSE;
   $rcmail_config['vacation_gui_vacationsubject'] = TRUE;
   $rcmail_config['vacation_gui_vacationmessage_html'] = TRUE;
   $rcmail_config['vacation_gui_vacationkeepcopyininbox'] = FALSE;
   $rcmail_config['vacation_gui_vacationforwarder'] = TRUE;
 +-----------------------------------------------------------------------+
 */

// Array_search with partial matches and optional search by key
// http://www.php.net/manual/en/function.array-search.php#95926
function array_find($needle, $haystack, $search_keys = false)
{
    if (!is_array($haystack))
        return false;
    foreach ($haystack as $key=>$value)
    {
        $what = ($search_keys) ? $key : $value;
        if (strpos($what, $needle) !== false)
            return $key;
    }
    return false;
}

// Parse output from Plesk CLI autoresponder
function parse_output($output, $strkey)
{
    $key = array_find($strkey, $output);
    if ($key !== false)
        return substr($output[$key], 20);
    else
        return false;
}


/*
 * Read driver function.
 *
 * @param array $data the array of data to get and set.
 *
 * @return integer the status code.
 */

function vacation_read(array &$data)
{
	$rcmail = rcmail::get_instance();

    $email = escapeshellcmd($data['email']);

    $cmd = "sudo /opt/psa/bin/autoresponder -i -mail $email";
    exec($cmd . ' 2>&1', $output, $returnvalue);

    if ($returnvalue !== 0)
    {
        $stroutput = implode(' ', $output);
        raise_error(array(
            'code' => 600,
            'type' => 'php',
            'file' => __FILE__, 'line' => __LINE__,
            'message' => "Vacation plugin: Unable to execute $cmd ($stroutput, $returnvalue)"
            ), true, false);
        return PLUGIN_ERROR_CONNECT;
    }

    $status = parse_output($output, "Status");
    if ($status == "true")
        $data['vacation_enable'] = true;
    $data['vacation_subject'] = parse_output($output, "Answer with subj:");
    $data['vacation_message'] = parse_output($output, "Answer text:");
    $data['vacation_forwarder'] = parse_output($output, "Forward request:");

/*
    Fields not currently used by Plesk:
	$data['vacation_start']
	$data['vacation_end']
	$data['vacation_keepcopyininbox']
*/

	return PLUGIN_SUCCESS;
}

/*
 * Write driver function.
 *
 * @param array $data the array of data to get and set.
 *
 * @return integer the status code.
 */
function vacation_write(array &$data)
{
	$rcmail = rcmail::get_instance();

    $email = escapeshellcmd($data['email']);
    if ($data['vacation_enable'])
        $status = "true";
    else
        $status = "false";
    $subject = escapeshellcmd($data['vacation_subject']);
    if ($rcmail->config->get('vacation_gui_vacationmessage_html'))
    {
        $format = "html";
        $text = str_replace("'", "&#39;", $data['vacation_message']);
    }
    else
    {
        $format = "plain";
        $text = escapeshellcmd($data['vacation_message']);
    }
    $redirect = escapeshellcmd($data['vacation_forwarder']);

    $cmd = sprintf("sudo /opt/psa/bin/autoresponder -u -mail %s -status %s -subject '%s' -text '%s' -format %s -redirect '%s'",
        $email, $status, $subject, $text, $format, $redirect);
    exec($cmd . ' 2>&1', $output, $returnvalue);

    if ($returnvalue == 0) {
        return PLUGIN_SUCCESS;
    }
    else
    {
        $stroutput = implode(' ', $output);
        raise_error(array(
            'code' => 600,
            'type' => 'php',
            'file' => __FILE__, 'line' => __LINE__,
            'message' => "Vacation plugin: Unable to execute $cmd ($stroutput, $returnvalue)"
            ), true, false);
    }

    return PLUGIN_ERROR_PROCESS;
}
