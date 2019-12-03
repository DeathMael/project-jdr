<?php

namespace App\Service;

class CalendarService {

	public function service() {
// Initialise the client.
		$client = new \Google_Client();
// Set the application name, this is included in the User-Agent HTTP header.
		$client->setApplicationName('Google Calendar API PHP Quickstart');
// Set the authentication credentials we downloaded from Google.
		$client->setAuthConfig(__DIR__ . '/credentials.json');
// Setting offline here means we can pull data from the venue's calendar when they are not actively using the site.
		$client->setAccessType("offline");
// Add the Google Calendar scope to the request.
		$client->setScopes(\Google_Service_Calendar::CALENDAR);
// Set the redirect URL back to the site to handle the OAuth2 response. This handles both the success and failure journeys.

		$client->setPrompt('select_account consent');

		$tokenPath = __DIR__ . '/token.json';

		$service = new \Google_Service_Calendar($client);

		if (file_exists($tokenPath)) {

			$accessToken = json_decode(file_get_contents($tokenPath), true);
			$client->setAccessToken($accessToken);

		}

		return $service;

	}
}