<?php

namespace App\Form;

use App\Entity\Events;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventsType extends AbstractType {
	public function buildForm(FormBuilderInterface $builder, array $options) {
		$builder
			->add('date', DateType::class)
			->add('hour', TimeType::class)
			->add('description')
			->add('title')
			->add('start')
			->add('end')
		;
	}

	public function configureOptions(OptionsResolver $resolver) {
		$resolver->setDefaults([
			'data_class' => Events::class,
		]);
	}
}
