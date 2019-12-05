<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('beginAt')
			->add('endAt')
			->add('title')
			->add('description')
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
