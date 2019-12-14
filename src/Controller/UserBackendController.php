<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class UserBackendController extends EasyAdminController
{
    /**
     * The method that is executed when the user performs a 'new' action on an entity.
     *
     * @return Response|RedirectResponse
     */
    protected function newAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_NEW);
        $entity = $this->executeDynamicMethod('createNew<EntityName>Entity');
        $easyadmin = $this->request->attributes->get('easyadmin');
        $easyadmin['item'] = $entity;
        $this->request->attributes->set('easyadmin', $easyadmin);
        $fields = $this->entity['new']['fields'];
        $newForm = $this->executeDynamicMethod('create<EntityName>NewForm', [$entity, $fields]);
        $newForm->handleRequest($this->request);
        if ($newForm->isSubmitted() && $newForm->isValid()) {
            $this->processUploadedFiles($newForm);
            $class = $this->entity['class'];
            $em = $this->getDoctrine()->getManagerForClass($class);
            $ids = $this->getDoctrine()->getRepository(User::class)->findAll();
            $error = 0;
            foreach ($ids as $id) {
                $user = $em->find($class, $id);
                if ($entity->getUsername() == $user->getUsername()) {
                    $this->addFlash('error', 'Le nom d\'utilisateur ' . $user->getUsername() . ' est déjà pris !');
                    $error++;
                }
                if ($entity->getEmail() == $user->getEmail()) {
                    $this->addFlash('error', 'L\'adresse email ' . $user->getEmail() . ' est déjà prise par ' . $user->getUsername() . ' !');
                    $error++;
                }
                if ($error > 0)
                    return $this->redirectToRoute('easyadmin', array(
                        'action' => 'new',
                        'entity' => $this->request->query->get('entity'),
                    ));
            }
            $this->addFlash('success', 'L\'utilisateur '.$entity->getUsername() . ' est désormais inscrit en tant qu\''.$entity->getFormatedRank().' !');
            $this->dispatch(EasyAdminEvents::PRE_PERSIST, ['entity' => $entity]);
            $this->executeDynamicMethod('persist<EntityName>Entity', [$entity, $newForm]);
            $this->dispatch(EasyAdminEvents::POST_PERSIST, ['entity' => $entity]);
            return $this->redirectToReferrer();
        }
        $this->dispatch(EasyAdminEvents::POST_NEW, [
            'entity_fields' => $fields,
            'form' => $newForm,
            'entity' => $entity,
        ]);
        $parameters = [
            'form' => $newForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
        ];
        return $this->executeDynamicMethod('render<EntityName>Template', ['new', $this->entity['templates']['new'], $parameters]);
    }

    /**
     * The method that is executed when the user performs a 'edit' action on an entity.
     *
     * @return Response|RedirectResponse
     *
     * @throws \RuntimeException
     */
    protected function editAction()
    {
        $this->dispatch(EasyAdminEvents::PRE_EDIT);
        $id = $this->request->query->get('id');
        $easyadmin = $this->request->attributes->get('easyadmin');
        $entity = $easyadmin['item'];
        if ($this->request->isXmlHttpRequest() && $property = $this->request->query->get('property')) {
            $newValue = 'true' === mb_strtolower($this->request->query->get('newValue'));
            $fieldsMetadata = $this->entity['list']['fields'];
            if (!isset($fieldsMetadata[$property]) || 'toggle' !== $fieldsMetadata[$property]['dataType']) {
                throw new \RuntimeException(sprintf('The type of the "%s" property is not "toggle".', $property));
            }
            $this->updateEntityProperty($entity, $property, $newValue);
            // cast to integer instead of string to avoid sending empty responses for 'false'
            return new Response((int) $newValue);
        }
        $fields = $this->entity['edit']['fields'];
        $editForm = $this->executeDynamicMethod('create<EntityName>EditForm', [$entity, $fields]);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);
        $editForm->handleRequest($this->request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if ($entity instanceof User) {
                $class = $this->entity['class'];
                $em = $this->getDoctrine()->getManagerForClass($class);
                $ids = $this->getDoctrine()->getRepository(User::class)->findAll();
                $error = 0;
                foreach ($ids as $id) {
                    $user = $em->find($class, $id);
                    if ($entity != $user && $entity->getUsername() == $user->getUsername()) {
                        $this->addFlash('error', 'Le nom d\'utilisateur ' . $user->getUsername() . ' est déjà pris !');
                        $error++;
                    }
                    if ($entity != $user && $entity->getEmail() == $user->getEmail()) {
                        $this->addFlash('error', 'L\'adresse email ' . $user->getEmail() . ' est déjà prise par ' . $user->getUsername() . ' !');
                        $error++;
                    }
                    if ($error > 0)
                        return $this->redirectToRoute('easyadmin', array(
                            'action' => 'edit',
                            'id' => $this->request->query->get('id'),
                            'entity' => $this->request->query->get('entity'),
                        ));
                }
                $this->addFlash('success', 'L\'utilisateur ' . $entity->getUsername(). ' a été modifié avec succcès !');
            }
            $this->processUploadedFiles($editForm);
            $this->dispatch(EasyAdminEvents::PRE_UPDATE, ['entity' => $entity]);
            $this->executeDynamicMethod('update<EntityName>Entity', [$entity, $editForm]);
            $this->dispatch(EasyAdminEvents::POST_UPDATE, ['entity' => $entity]);
            return $this->redirectToReferrer();
        }
        $this->dispatch(EasyAdminEvents::POST_EDIT);
        $parameters = [
            'form' => $editForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];
        return $this->executeDynamicMethod('render<EntityName>Template', ['edit', $this->entity['templates']['edit'], $parameters]);
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
            if ($this->getUser() == $entity) {
                $this->addFlash('error', 'Impossible de supprimer l\'utilisateur courant !');
                return $this->redirectToReferrer();
            }
            if ($entity instanceof User)
                $this->addFlash('success', 'L\'utilisateur '.$entity->getUsername().' a été supprimé avec succès!');
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

    protected function deleteBatchAction(array $ids): void
    {
        $class = $this->entity['class'];
        $primaryKey = $this->entity['primary_key_field_name'];
        $users='';

        $entities = $this->em->getRepository($class)
            ->findBy([$primaryKey => $ids]);

        foreach ($entities as $entity) {
            $this->em->remove($entity);
            $users.=$entity->getUsername().', ';
        }
        $this->addFlash('success', 'Les utilisateurs : '.$users.'ont été supprimés avec succès !');
        $this->em->flush();
    }

    public function promoteAction()
    {
        $id = $this->request->query->get('id');
        $entity = $this->em->getRepository(User::class)->find($id);
        if ($entity->getFormatedRank() != 'Orbis Primus') {
            if ($entity->getFormatedRank() == 'Orbis Tertius') {
                $entity->setRank(1);
            } else {
                $entity->setRank(2);
            }
            $this->addFlash('success', 'L\'utilisateur '.$entity->getUsername().' a été promu au rang de '.$entity->getRank());
            $this->em->flush();
        }
        else $this->addFlash('error', 'Impossible de promouvoir '.$entity->getUsername().', le rang maximal atteignable est '.$entity->getFormatedRank().' !');
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));
    }

    public function demoteAction()
    {
        $id = $this->request->query->get('id');
        $entity = $this->em->getRepository(User::class)->find($id);
        if ($entity->getFormatedRank() != 'Orbis Tertius') {
            if ($entity->getFormatedRank() == 'Orbis Primus') {
                $entity->setRank(1);
            } else {
                $entity->setRank(0);
            }
            $this->addFlash('success', 'L\'utilisateur '.$entity->getUsername().' a été rétrogradé au rang de '.$entity->getFormatedRank());
            $this->em->flush();
        }
        else $this->addFlash('error', 'Impossible de rétrograder '.$entity->getUsername().', le rang minimal atteignable est '.$entity->getFormatedRank().' !');
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));
    }

    public function SeveralpromoteBatchAction(array $ids)
    {
        $class = $this->entity['class'];
        $em = $this->getDoctrine()->getManagerForClass($class);
        $unpromoted='';
        foreach ($ids as $id) {
            $user = $em->find($class, $id);
            if ($user->getFormatedRank() != 'Orbis Primus') {
                if ($user->getFormatedRank() == 'Orbis Tertius') {
                    $user->setRank(1);
                } else {
                    $user->setRank(2);
                }
            }
            else {
                $unpromoted.=$user->getUsername().', ';
            }
        }
        if ($unpromoted!='')
            $this->addFlash('error', 'Les utilisateurs '.$unpromoted. 'n\'ont pas pu être promu !');
        else $this->addFlash('success', 'Les utilisateurs sélectionnés ont tous été promu');
        $this->em->flush();
    }


    public function SeveraldemoteBatchAction(array $ids)
    {
        $class = $this->entity['class'];
        $em = $this->getDoctrine()->getManagerForClass($class);
        $undemoted='';
        foreach ($ids as $id) {
            $user = $em->find($class, $id);
            if ($user->getFormatedRank() != 'Orbis Tertius') {
                if ($user->getFormatedRank() == 'Orbis Primus') {
                    $user->setRank(1);
                } else {
                    $user->setRank(0);
                }
            }
            else {
                $undemoted.=$user->getUsername().', ';
            }
        }
        if ($undemoted!='')
            $this->addFlash('error', 'Les utilisateurs '.$undemoted. 'n\'ont pas pu être retrogradés !');
        else $this->addFlash('success', 'Les utilisateurs sélectionnés ont tous été rétrogradé');
        $this->em->flush();
    }
}