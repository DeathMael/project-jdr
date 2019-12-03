<?php

namespace App\Controller;

use App\Service\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController {
	/**
	 * @Route("/google", name="google")
	 */
	public function index() {

		$service = CalendarService::service();

		// Print the next 10 events on the user's calendar.
		$calendarId = 'primary';
		$optParams = array(
			'maxResults' => 10,
			'orderBy' => 'startTime',
			'singleEvents' => true,
			'timeMin' => date('c'),
		);

		$results = $service->events->listEvents($calendarId, $optParams);
		$events = $results->getItems();

		foreach ($events as $event) {
			dump($event->getDescription());

		}

		$client = new \Google_Client();
		return $this->render('google/index.html.twig', [
			'controller_name' => 'GoogleController',
		]);
	}
}
