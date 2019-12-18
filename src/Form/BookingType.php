<?php

namespace App\Form;

use App\Entity\Booking;
use App\Service\CalendarService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType {

	public function buildForm(FormBuilderInterface $builder, array $options) {

		$booking = new Booking();
		$service = CalendarService::service();

		$builder
			->add('beginAt', DateTimeType::class)
			->add('endAt', DateTimeType::class)
			->add('title', TextType::class)
			->add('description', TextareaType::class)
			/*->add('updated_at')
            ->add('created_at')*/
			//->add('google_id')
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'data_class' => Booking::class,
		]);
	}
}
