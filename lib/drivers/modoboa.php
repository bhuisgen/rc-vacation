<?php

/**
 * Modoboa rc-vacation Driver
 *
 * @version 1.0.2
 * @author stephane @actionweb
 *
 * Copyright (C) 2018, The Roundcube Dev Team
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://www.gnu.org/licenses/.
 *
 * You need to define in plugins/vacation/config.inc.php theses variables:
 *
 * $rcmail_config['vacation_driver'] = 'modoboa'; // Driver used for backend storage
 * $rcmail_config['token_api_modoboa'] = ''; // Put token number from Modoboa server
 * $rcmail_config['vacation_log'] = true; // To activate logs
 *
 */

/*
 * Read driver function.
 * @param array $data the array of data to get and set.
 * @return integer the status code.
 */
function vacation_read(array &$data)
{
  // Init config access
  $rcmail = rcmail::get_instance();
  $ModoboaToken = $rcmail->config->get('token_api_modoboa', '');

  $RoudCubeUsername = $_SESSION['username'];
  $IMAPhost = $_SESSION['imap_host'];

  // Call GET to fetch values from modoboa server
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://" . $IMAPhost . "/api/v1/armessages/?accounts=" . $RoudCubeUsername,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
      "Authorization: Token " . $ModoboaToken,
      "Cache-Control: no-cache",
      "Content-Type: application/json"
    ),
  ));

  $response = curl_exec($curl);

  $err = curl_error($curl);
  curl_close($curl);

  if ($err) {
    return PLUGIN_ERROR_PROCESS;
    rcube::write_log('errors', "Modoboa cURL Error #: " . $err);
  }

  // Decode json string
  $decoded = json_decode($response);

  // Set id
  $userid = $decoded[0]->id;

  // Set mbox
  $mbox = $decoded[0]->mbox;

  // Set message
  $data['vacation_subject'] = $decoded[0]->subject;
  $data['vacation_message'] = $decoded[0]->content;
  $data['vacation_enable'] = $decoded[0]->enabled; // boolean

  // Set dates
  $data['vacation_start'] = strtotime($decoded[0]->fromdate);
  $data['vacation_end'] = strtotime($decoded[0]->untildate);

  return PLUGIN_SUCCESS;
}

/*
 * Write driver function.
 * @param array $data the array of data to get and set.
 * @return integer the status code.
 */
function vacation_write(array &$data)
{

  // Init config access
  $rcmail = rcmail::get_instance();
  $ModoboaToken = $rcmail->config->get('token_api_modoboa', '');

  $RoudCubeUsername = $_SESSION['username'];
  $IMAPhost = $_SESSION['imap_host'];

  // Call GET to fetch values from modoboa server
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://" . $IMAPhost . "/api/v1/armessages/?accounts=" . $RoudCubeUsername,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
      "Authorization: Token " . $ModoboaToken,
      "Cache-Control: no-cache",
      "Content-Type: application/json"
    ),
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    return PLUGIN_ERROR_PROCESS;
    rcube::write_log('errors', "Modoboa cURL Error #: " . $err);
  }

  // Decode json string
  $decoded = json_decode($response);

  // Set id
  $userid = $decoded[0]->id;
  rcube::write_log('errors', "userid write: " . $userid);
  $ret['id'] = $userid;
  rcube::write_log('errors', "ret id: " . $ret['id']);

  // Set message
  $ret['subject'] = $data['vacation_subject'];
  $ret['content'] = $data['vacation_message'];
  $ret['enabled'] = $data['vacation_enable'];

  // Set vacation_start
  $ret['fromdate'] = date("Y-m-d\TH:i:sO", $data['vacation_start']);

  // Set vacation_end
  $todayDate = date("Y-m-d", time());
  $endDate = date("Y-m-d", $data['vacation_end']);

  if(strtotime($todayDate) < strtotime($endDate))
  {
    $ret['untildate'] = date("Y-m-d\TH:i:sO", $data['vacation_end']);
  } else {
    return PLUGIN_ERROR_DATE;
  }

  // Set mbox
  $mbox = $decoded[0]->mbox;
  $ret['mbox'] = $mbox;

  // Encode json
  $encoded = json_encode($ret);

  // Call HTTP API Modoboa
  $curl = curl_init();

  curl_setopt_array($curl, array(
    CURLOPT_URL => "https://" . $IMAPhost . "/api/v1/armessages/" . $userid . "/?mbox=" . $mbox,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "PUT",
    CURLOPT_POSTFIELDS => "" . $encoded . "",
    CURLOPT_HTTPHEADER => array(
      "Authorization: Token " . $ModoboaToken,
      "Cache-Control: no-cache",
      "Content-Type: application/json"
    ),
  ));

  $response = curl_exec($curl);
  $err = curl_error($curl);

  curl_close($curl);

  if ($err) {
    return PLUGIN_ERROR_PROCESS;
    rcube::write_log('errors', "Modoboa cURL Error #: " . $err);
  }

	return PLUGIN_SUCCESS;
}
