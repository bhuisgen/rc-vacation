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
	
	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email');
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email']);	
	$ldap_basedn = str_replace($search, $replace, $rcmail->config->get('vacation_ldap_basedn'));
	$ldap_binddn = str_replace($search, $replace, $rcmail->config->get('vacation_ldap_binddn'));

	$search = array('%username',
					'%password',
					'%email_local',
					'%email_domain',
					'%email');
	$replace = array($data['username'],
					 $rcmail->decrypt($_SESSION['password']),
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email']);
	$ldap_bindpw = str_replace($search, $replace, $rcmail->config->get('vacation_ldap_bindpw'));
	
	$ldapConfig = array (
        'host'      => $rcmail->config->get('vacation_ldap_host'),
        'port'      => $rcmail->config->get('vacation_ldap_port'),
        'starttls'  => $rcmail->config->get('vacation_ldap_starttls'),
        'version'   => $rcmail->config->get('vacation_ldap_version'),
        'basedn'    => $ldap_basedn,
        'binddn'    => $ldap_binddn,
        'bindpw'    => $ldap_bindpw,
	);

	$ldap = Net_LDAP2::connect($ldapConfig);
	if (PEAR::isError($ldap))
	{
		return PLUGIN_ERROR_CONNECT;
	}

	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email',
					'%vacation_enable',
					'%vacation_start',
					'%vacation_end',
					'%vacation_subject',
					'%vacation_message',
					'%vacation_keepcopyininbox',
					'%vacation_forwarder');
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email'],
					 $data['vacation_enable'],
					 $data['vacation_start'],
					 $data['vacation_end'],
					 $data['vacation_subject'],
					 $data['vacation_message'],
					 $data['vacation_keepcopyininbox'],
					 $data['vacation_forwarder']);

	$search_base = str_replace($search, $replace, $rcmail->config->get('vacation_ldap_search_base'));
	$search_filter = str_replace($search, $replace, $rcmail->config->get('vacation_ldap_search_filter'));
	$search_params = array('attributes' => $rcmail->config->get('vacation_ldap_search_attrs'));

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
		$data['email'] = $entry->get_value($rcmail->config->get('vacation_ldap_search_attr_email'));
	}

	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_emaillocal')))
	{
		$data['email_local'] = $entry->get_value($rcmail->config->get('vacation_ldap_search_attr_emaillocal'));
	}

	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_emaildomain')))
	{
		$data['email_domain'] = $entry->get_value($rcmail->config->get('vacation_ldap_search_attr_emaildomain'));
	}
	
	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_vacationenable')))
	{
		if ($entry->get_value($rcmail->config->get('vacation_ldap_attr_vacationenable')) ==	$rcmail->config->get('vacation_ldap_attr_vacationenable_value_enabled'))
			$data['vacation_enable'] = 1;
		else
			$data['vacation_enable'] = 0;
	}
	
	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_vacationstart')))
	{
		$data['vacation_start'] = $entry->get_value($rcmail->config->get('vacation_ldap_attr_vacationstart'));
	}
	
	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_vacationend')))
	{
		$data['vacation_end'] = $entry->get_value($rcmail->config->get('vacation_ldap_attr_vacationend'));
	}
	
	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_vacationsubject')))
	{
		$data['vacation_subject'] = $entry->get_value($rcmail->config->get('vacation_ldap_attr_vacationsubject'));
	}

	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_vacationmessage')))
	{
		$data['vacation_message'] = $entry->get_value($rcmail->config->get('vacation_ldap_attr_vacationmessage'));
	}
	
	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_vacationkeepcopyininbox')))
	{
		if ($entry->get_value($rcmail->config->get('vacation_ldap_attr_vacationkeepcopyininbox')) ==	$rcmail->config->get('vacation_ldap_attr_vacationkeepcopyininbox_value_enabled'))
			$data['vacation_keepcopyininbox'] = 1;
		else
			$data['vacation_keepcopyininbox'] = 0;
	}
	
	if ($entry->exists($rcmail->config->get('vacation_ldap_attr_vacationforwarder')))
	{
		$data['vacation_forwarder'] = $entry->get_value($rcmail->config->get('vacation_ldap_attr_vacationforwarder'));
	}

	$ldap->done();

	return PLUGIN_SUCCESS;
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
	
	$search = array('%username',
					'%email_local',
					'%email_domain',
					'%email');
	$replace = array($data['username'],
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email']);	
	$ldap_basedn = str_replace($search, $replace, $rcmail->config->get('vacation_ldap_basedn'));
	$ldap_binddn = str_replace($search, $replace, $rcmail->config->get('vacation_ldap_binddn'));

	$search = array('%username',
					'%password',
					'%email_local',
					'%email_domain',
					'%email');
	$replace = array($data['username'],
					 $rcmail->decrypt($_SESSION['password']),
					 $data['email_local'],
					 $data['email_domain'],
					 $data['email']);
	$ldap_bindpw = str_replace($search, $replace, $rcmail->config->get('vacation_ldap_bindpw'));
	
	$ldapConfig = array (
        'host'      => $rcmail->config->get('vacation_ldap_host'),
        'port'      => $rcmail->config->get('vacation_ldap_port'),
        'starttls'  => $rcmail->config->get('vacation_ldap_starttls'),
        'version'   => $rcmail->config->get('vacation_ldap_version'),
        'basedn'    => $ldap_basedn,
        'binddn'    => $ldap_binddn,
        'bindpw'    => $ldap_bindpw,
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
		$search = array('%username',
						'%email_local',
						'%email_domain',
						'%email',
						'%vacation_enable',
						'%vacation_start',
						'%vacation_end',
						'%vacation_subject',
						'%vacation_message',
						'%vacation_keepcopyininbox',
						'%vacation_forwarder',
		);
		$replace = array($data['username'],
						 $data['email_local'],
						 $data['email_domain'],
						 $data['email'],
						 ($data['vacation_enable'] ? "TRUE" : "FALSE"),
						 $data['vacation_start'],
						 $data['vacation_end'],
						 $data['vacation_subject'],
						 $data['vacation_message'],
						 $data['vacation_keepcopyininbox'],
						 $data['vacation_forwarder']
		);
		$dns[$i] = str_replace($search, $replace, $dns[$i]);

		foreach ($ops[$i] as $op => $args)
		{
			foreach ($args as $key => $value)
			{
				$search = array('%username',
								'%email_local',
								'%email_domain',
								'%email',
								'%vacation_enable',
								'%vacation_start',
								'%vacation_end',
								'%vacation_subject',
								'%vacation_message',
								'%vacation_keepcopyininbox',
								'%vacation_forwarder'
				);
				$replace = array($data['username'],
								 $data['email_local'],
								 $data['email_domain'],
								 $data['email'],
								($data['vacation_enable'] ?
									$rcmail->config->get('vacation_ldap_attr_vacationenable_value_enabled') :
									$rcmail->config->get('vacation_ldap_attr_vacationenable_value_disabled')),
								$data['vacation_start'],
								$data['vacation_end'],
								$data['vacation_subject'],
								$data['vacation_message'],
								$data['vacation_keepcopyininbox'],
								$data['vacation_forwarder']
				);
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

	return PLUGIN_SUCCESS;
}
