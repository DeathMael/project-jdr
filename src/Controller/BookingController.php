<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Service\CalendarService;
use Google_Service_Calendar_Event;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/booking")
 */
class BookingController extends AbstractController {

	/**
	 * @Route("/", name="booking_index", methods={"GET"})
	 */
	public function index(BookingRepository $bookingRepository): Response{

		$service = CalendarService::service();

		$calendarId = 'primary';
		$optParams = array(
			'maxResults' => 10,
			'orderBy' => 'startTime',
			'singleEvents' => true,
			'timeMin' => date('c'),
		);

		return $this->render('booking/index.html.twig', [
			'bookings' => $bookingRepository->findAll(),
		]);
	}

	/**
	 * @Route("/sync", name="booking_sync")
	 */
	public function sync(BookingRepository $bookingRepository) {

		//Permet de synchroniser le calendar avec l'API Google
		$service = CalendarService::service();
		$results = $service->events->listEvents('primary');
		//Récupération de toutes les valeurs des évènement GoogleCalendar
		$events = $results->getItems();

		foreach ($events as $event) {
			//utilisation de Doctrine pour savoir si l'évènement existe déjà
			if ($bookingRepository->findOneByGoogleid($event->getId()) == NULL) {

				$booking = new Booking();

				$booking->setTitle($event->getSummary());
				$booking->setDescription($event->getDescription());
				$booking->setBeginAt(
					new \DateTime($event->getStart()->getDateTime(), new \DateTimeZone('Europe/Paris')));
				$booking->setEndAt(
					new \DateTime($event->getEnd()->getDateTime(), new \DateTimeZone('Europe/Paris')));
				$booking->setGoogleid($event->getId());

				$entityManager = $this->getDoctrine()->getManager();

				$entityManager->persist($booking);

				$entityManager->flush();

			};

		};

		return $this->redirectToRoute('booking_calendar');
	}

	/**
	 * @Route("/calendar", name="booking_calendar", methods={"GET"})
	 */
	public function calendar(): Response{

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
/*
foreach ($events as $event) {
dump($event->getDescription());

}

$client = new \Google_Client();
$client->getClientId();
dump($client);*/

		return $this->render('booking/calendar.html.twig', ['controller_name' => 'BookingController']);
	}

	/**
	 * @Route("/new", name="booking_new", methods={"GET","POST"})
	 */
	public function new (Request $request): Response{

		//Création d'une réunion
		$booking = new Booking();
		//Appel du service créé pour avoir l'accès client a l'API GoogleCalendar
		$service = CalendarService::service();
		$form = $this->createForm(BookingType::class, $booking);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {

			$entityManager = $this->getDoctrine()->getManager();
			//Persistance de cet évènement en bdd
			$entityManager->persist($booking);

			//Définition du Calendrier google
			$calendarId = 'primary';

			//Création d'un nouvel event et définition des valeurs attendues par la base de donnée de Google
			$event = new \Google_Service_Calendar_Event([
				'summary' => $booking->getTitle(),
				'description' => $booking->getDescription(),
				//Formatage de la date pour le bon fuseau horraire.
				'start' => ['dateTime' => date_format($booking->getBeginAt(), "Y-m-d\TH:i:s"),
					'timeZone' => 'Europe/Paris'],
				'end' => ['dateTime' => date_format($booking->getEndAt(), "Y-m-d\TH:i:s"),
					'timeZone' => 'Europe/Paris'],
			]);
			//création d'évenement du coté de google
			$results = $service->events->insert($calendarId, $event);
			//écriture en bdd de l'id de cet évènement google pour pouvoir le modifier par la suite
			$booking->setGoogleid($results->getId());
			$entityManager->flush();

			return $this->redirectToRoute('booking_calendar');
		}

		return $this->render('booking/new.html.twig', [
			'booking' => $booking,
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/{id}", name="booking_show", methods={"GET"})
	 */
	public function show(Booking $booking): Response {
		return $this->render('booking/show.html.twig', [
			'booking' => $booking,
		]);
	}

	/**
	 * @Route("/{id}/edit", name="booking_edit", methods={"GET","POST"})
	 */
	public function edit(Request $request, Booking $booking): Response{
		$form = $this->createForm(BookingType::class, $booking);

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$service = CalendarService::service();

			$event = $service->events->get('primary', $booking->getGoogleid());

			$event = new \Google_Service_Calendar_Event([
				'summary' => $booking->getTitle(),
				'description' => $booking->getDescription(),
				'start' => ['dateTime' => date_format($booking->getBeginAt(), "Y-m-d\TH:i:s"),
					'timeZone' => 'Europe/Paris'],
				'end' => ['dateTime' => date_format($booking->getEndAt(), "Y-m-d\TH:i:s"),
					'timeZone' => 'Europe/Paris'],
			]);

			$updatedEvent = $service->events->update('primary', $booking->getGoogleid(), $event);

			$this->getDoctrine()->getManager()->flush();

			return $this->redirectToRoute('booking_calendar');
		}

		return $this->render('booking/edit.html.twig', [
			'booking' => $booking,
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/{id}", name="booking_delete", methods={"DELETE"})
	 */
	public function delete(Request $request, Booking $booking): Response {
		if ($this->isCsrfTokenValid('delete' . $booking->getId(), $request->request->get('_token'))) {
			$entityManager = $this->getDoctrine()->getManager();
			$service = CalendarService::service();
			$service->events->delete('primary', $booking->getGoogleid());
			$entityManager->remove($booking);
			$entityManager->flush();
		}

		return $this->redirectToRoute('booking_calendar');
	}

}
