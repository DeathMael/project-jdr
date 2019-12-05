<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException;

class UserBackendController extends EasyAdminController
{
    public function createNewUserEntity()
    {
        return $this->get('fos_user.user_manager')->createUser();
    }

    public function persistUserEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
        parent::persistEntity($user);
    }

    public function updateUserEntity($user)
    {
        $this->get('fos_user.user_manager')->updateUser($user, false);
        parent::updateEntity($user);
    }

    protected function deleteAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_DELETE);

        if ('DELETE' !== $this->request->getMethod()) {
            return $this->redirect($this->generateUrl('easyadmin', ['action' => 'list', 'entity' => $this->entity['name']]));
        }

        $id = $this->request->query->get('id');
        $form = $this->createDeleteForm($this->entity['name'], $id);
        $form->handleRequest($this->request);

        if ($form->isSubmitted() && $form->isValid()) {
            $easyadmin = $this->request->attributes->get('easyadmin');
            $entity = $easyadmin['item'];

            $this->dispatch(EasyAdminEvents::PRE_REMOVE, ['entity' => $entity]);
            if ($this->getUser()==$entity)
            {
                $this->addFlash('error', 'Impossible de supprimer l\'utilisateur courant !');
                return $this->redirectToReferrer();
            }
            try {
                $this->executeDynamicMethod('remove<EntityName>Entity', [$entity, $form]);
            } catch (ForeignKeyConstraintViolationException $e) {
                throw new EntityRemoveException(['entity_name' => $this->entity['name'], 'message' => $e->getMessage()]);
            }

            $this->dispatch(EasyAdminEvents::POST_REMOVE, ['entity' => $entity]);
        }

        $this->dispatch(EasyAdminEvents::POST_DELETE);

        return $this->redirectToReferrer();
    }

    public function promoteAction()
    {
        $id = $this->request->query->get('id');
        $entity = $this->em->getRepository(User::class)->find($id);
        $entity->setRank($entity->getRank()+1);
        $this->em->flush();

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));
    }

    public function demoteAction()
    {
        $id = $this->request->query->get('id');
        $entity = $this->em->getRepository(User::class)->find($id);
        $entity->setRank($entity->getRank()-1);
        $this->em->flush();

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));
    }

    public function SeveralpromoteBatchAction(array $ids)
    {
        $class = $this->entity['class'];
        $em = $this->getDoctrine()->getManagerForClass($class);

        foreach ($ids as $id) {
            $user = $em->find($class, $id);
            $user->setRank($user->getRank()+1);
        }

        $this->em->flush();

    }

    public function SeveraldemoteBatchAction(array $ids)
    {
        $class = $this->entity['class'];
        $em = $this->getDoctrine()->getManagerForClass($class);

        foreach ($ids as $id) {
            $user = $em->find($class, $id);
            $user->setRank($user->getRank()-1);
        }

        $this->em->flush();

    }
}