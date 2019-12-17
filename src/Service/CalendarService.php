<?php

namespace App\Service;

class CalendarService {

	public function service() {
// Initialise le client.
		$client = new \Google_Client();
// Utilisation de l'application quickstart pour l'API GoogleCalendar.
		$client->setApplicationName('Google Calendar API PHP Quickstart');
// Donne la route pour le fichier credential.json correspondant
		$client->setAuthConfig(__DIR__ . '/credentials.json');
// Setting offline here means we can pull data from the venue's calendar when they are not actively using the site.
		$client->setAccessType("offline");
// Indique le service que l'on vas utiliser.
		$client->setScopes(\Google_Service_Calendar::CALENDAR);
// Set the redirect URL back to the site to handle the OAuth2 response. This handles both the success and failure journeys.

		$client->setPrompt('select_account consent');

//Donne la direction de fichier token.json
		$tokenPath = __DIR__ . '/token.json';

//Création d'un nouveau service
		$service = new \Google_Service_Calendar($client);

		if (file_exists($tokenPath)) {

			$accessToken = json_decode(file_get_contents($tokenPath), true);
			//Donne l'accès client à ce service
			$client->setAccessToken($accessToken);

		}

		return $service;

	}
}