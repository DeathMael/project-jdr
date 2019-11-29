<?php

namespace App\EventListener;

use App\Repository\EventRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;

class CalendarListener {
	private $eventRepository;

	public function __construct(
		EventRepository $eventRepository
	) {
		$this->eventRepository = $eventRepository;
	}

	public function load(CalendarEvent $calendar): void{
		$start = $calendar->getStart();
		$end = $calendar->getEnd();
		$filters = $calendar->getFilters();

		// Modify the query to fit to your entity and needs
		// Change event.beginAt by your start date property
		$events = $this->eventRepository
			->createQueryBuilder('event')
			->where('event.beginAt BETWEEN :start and :end')
			->setParameter('start', $start->format('Y-m-d H:i:s'))
			->setParameter('end', $end->format('Y-m-d H:i:s'))
			->getQuery()
			->getResult()
		;

		foreach ($events as $event) {
			// this create the events with your data (here event data) to fill calendar
			$eventEvent = new Event(
				$event->getTitle(),
				$event->getBeginAt(),
				$event->getEndAt() // If the end date is null or not defined, a all day event is created.
			);

			/*
				             * Add custom options to events
				             *
				             * For more information see: https://fullcalendar.io/docs/event-object
				             * and: https://github.com/fullcalendar/fullcalendar/blob/master/src/core/options.ts
			*/

			$eventEvent->setOptions([
				'backgroundColor' => 'red',
				'borderColor' => 'red',
			]);
			$eventEvent->addOption('url', 'https://github.com');

			// finally, add the event to the CalendarEvent to fill the calendar
			$calendar->addEvent($eventEvent);
		}
	}
}