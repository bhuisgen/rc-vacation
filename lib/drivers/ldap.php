<?php

/*
 +-----------------------------------------------------------------------+
 | lib/drivers/ldap.php                                                  |
 |                                                                       |
 | Copyright (C) 2009 Boris HUISGEN <bhuisgen@hbis.fr>                   |
 | Licensed under the GNU GPL                                            |
 +-----------------------------------------------------------------------+
 */

/*
 * Read driver function.
 *
 * @param array $data the array of data to get and set.
 *
 * @return boolean TRUE if load is successfull; FALSE otherwise.
 */
function vacation_read(array &$data)
{
	require_once ('Net/LDAP2.php');
	$rcmail = rcmail::get_instance();
	$ldapConfig = array (
        'host'      => $rcmail->config->get('vacation_ldap_host'),
        'port'      => $rcmail->config->get('vacation_ldap_port'),
        'starttls'  => $rcmail->config->get('vacation_ldap_starttls'),
        'version'   => $rcmail->config->get('vacation_ldap_version'),
        'basedn'    => $rcmail->config->get('vacation_ldap_basedn'),
		'binddn'    => $rcmail->config->get('vacation_ldap_binddn'),
        'bindpw'    => $rcmail->config->get('vacation_ldap_bindpw'),
	);

	$ldap = Net_LDAP2::connect($ldapConfig);
	if (PEAR::isError($ldap))
	{
		return PLUGIN_ERROR_CONNECT;
	}

	$search = array('%username', '%email_local', '%email_domain', '%email',
						'%vacation_enable', '%vacation_subject',
						'%vacation_message');
	$replace = array($data['username'], $data['email_local'],
	$data['email_domain'], $data['email'], $data['vacation_enable'],
	$data['vacation_subject'], $data['vacation_message']);

	$search_base = str_replace($search, $replace,
	$rcmail->config->get('vacation_ldap_search_base'));
	$search_filter = str_replace($search, $replace,
	$rcmail->config->get('vacation_ldap_search_filter'));
	$search_params = array(
	 'attributes' => $rcmail->config->get('vacation_ldap_search_attrs'));

	$search = $ldap->search($search_base, $search_filter, $search_params);
	if (Net_LDAP2::isError($userEntry))
	{
		$ldap->done();

		return PLUGIN_ERROR_PROCESS;
	}

	if ($search->count() < 1)
	{
		$ldap->done();

		return PLUGIN_ERROR_PROCESS;
	}

	$entry = $search->shiftEntry();
	
	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_email')))
	{
		$data['email'] = $entry->get_value(
		$rcmail->config->get('vacation_ldap_search_attr_email'));
	}

	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_emaillocal')))
	{
		$data['email_local'] = $entry->get_value(
		$rcmail->config->get('vacation_ldap_search_attr_emaillocal'));
	}

	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_emaildomain')))
	{
		$data['email_domain'] = $entry->get_value(
		$rcmail->config->get('vacation_ldap_search_attr_emaildomain'));
	}
	
	if ($entry->exists(
	$rcmail->config->get('vacation_ldap_attr_vacationenable')))
	{
		if ($entry->get_value($rcmail->config->get('vacation_ldap_attr_vacationenable')) ==
			$rcmail->config->get('vacation_ldap_attr_vacationenable_value_enabled'))
			$data['vacation_enable'] = 1;
		else
			$data['vacation_enable'] = 0;
	}
	
	if ($entry->exists(
	$rcmail->config->get('vacation_ldap_attr_vacationsubject')))
	{
		$data['vacation_subject'] = $entry->get_value(
		$rcmail->config->get('vacation_ldap_attr_vacationsubject'));
	}

	if ($entry->exists(
	$rcmail->config->get('vacation_ldap_attr_vacationmessage')))
	{
		$data['vacation_message'] = $entry->get_value(
		$rcmail->config->get('vacation_ldap_attr_vacationmessage'));
	}

	$ldap->done();

	return PLUGIN_NOERROR;
}

/*
 * Write driver function.
 *
 * @param array $data the array of data to get and set.
 *
 * @return boolean TRUE if save is successfull; FALSE otherwise.
 */
function vacation_write(array &$data)
{
	require_once ('Net/LDAP2.php');
	$rcmail = rcmail::get_instance();
	$ldapConfig = array (
        'host'      => $rcmail->config->get('vacation_ldap_host'),
        'port'      => $rcmail->config->get('vacation_ldap_port'),
        'starttls'  => $rcmail->config->get('vacation_ldap_starttls'),
        'version'   => $rcmail->config->get('vacation_ldap_version'),
        'basedn'    => $rcmail->config->get('vacation_ldap_basedn'),
	    'binddn'    => $rcmail->config->get('vacation_ldap_binddn'),
        'bindpw'    => $rcmail->config->get('vacation_ldap_bindpw'),
	);

	$ldap = Net_LDAP2::connect($ldapConfig);
	if (PEAR::isError($ldap))
	{
		return PLUGIN_ERROR_CONNECT;
	}

	$dns = $rcmail->config->get('vacation_ldap_modify_dns');
	$ops = $rcmail->config->get('vacation_ldap_modify_ops');
	
	for ($i = 0; $i < count($dns) && $i < count($ops); $i++)
	{
		$search = array('%username', '%email_local', '%email_domain', '%email',
						'%vacation_enable', '%vacation_subject',
						'%vacation_message');
		$replace = array($data['username'], $data['email_local'],
		$data['email_domain'], $data['email'], ($data['vacation_enable'] ? "TRUE" : "FALSE"),
		$data['vacation_subject'], $data['vacation_message']);
		$dns[$i] = str_replace($search, $replace, $dns[$i]);

		foreach ($ops[$i] as $op => $args)
		{
			foreach ($args as $key => $value)
			{
				$search = array('%username', '%email_local', '%email_domain',
					'%email', '%vacation_enable', '%vacation_subject',
					'%vacation_message');
				$replace = array($data['username'], $data['email_local'],
					$data['email_domain'], $data['email'],
					($data['vacation_enable'] ?
						$rcmail->config->get('vacation_ldap_attr_vacationenable_value_enabled') :
						$rcmail->config->get('vacation_ldap_attr_vacationenable_value_disabled')),
					$data['vacation_subject'], $data['vacation_message']);
				$ops[$i][$op][$key] = str_replace($search, $replace, $value);				
			}
		}

		$ret = $ldap->modify($dns[$i], $ops[$i]);
		if (PEAR::isError($ldap))
		{
			$ldap->done();
			
			return PLUGIN_ERROR_PROCESS;
		}
	}

	$ldap->done();

	return PLUGIN_NOERROR;
}
