<?php


namespace App\Controller;

use App\Entity\Project;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectBackendController extends EasyAdminController
{
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
            $users='';
            if ($entity instanceof Project) {

                foreach ($entity->getUsers() as $key=>$value) {
                    if ($value->getProject()!=null) {
                        $users.=$value->getUsername().' ,';
                    }
                }
            }
            if ($users!='') {
                $this->addFlash('warning', 'Les utilisateurs : '.$users.' ont quittés leurs projets respectifs !');
            }
            $this->addFlash('success', 'Le nouveau projet '.$entity->getStatuteType() .' a été créé avec succès ');

            $this->dispatch(EasyAdminEvents::PRE_PERSIST, ['entity' => $entity]);
            $this->executeDynamicMethod('persist<EntityName>Entity', [$entity, $newForm]);
            $this->dispatch(EasyAdminEvents::POST_PERSIST, ['entity' => $entity]);

            $this->updateEntity($entity);

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

    public function updateEntity($entity)
    {
        if ($entity instanceof Project)
        {
            $users = $entity->getUsers();
            foreach ($users as $key => $value) {
                $value->setProject($entity);
                parent::updateEntity($value);
            }
        }
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
            return new Response((int)$newValue);
        }

        $fields = $this->entity['edit']['fields'];

        $editForm = $this->executeDynamicMethod('create<EntityName>EditForm', [$entity, $fields]);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        $editForm->handleRequest($this->request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->processUploadedFiles($editForm);

            $this->dispatch(EasyAdminEvents::PRE_UPDATE, ['entity' => $entity]);
            $this->executeDynamicMethod('update<EntityName>Entity', [$entity, $editForm]);
            $this->dispatch(EasyAdminEvents::POST_UPDATE, ['entity' => $entity]);
            if ($entity instanceof Project)
                $this->addFlash('success', 'Le projet ' . $entity->getStatuteType().' n° '.$entity->getId().' a été modifié avec succcès !');
            return $this->redirectToReferrer();
        }

        $this->dispatch(EasyAdminEvents::POST_EDIT);

        $parameters = [
            'form' => $editForm->createView(),
            'entity_fields' => $fields,
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),
        ];

        if ($entity instanceof Project)
        {
            foreach ($entity->getUsers() as $key => $value) {
                $entity->removeUser($value);
                parent::updateEntity($value);
            }
        }

        return $this->executeDynamicMethod('render<EntityName>Template', ['edit', $this->entity['templates']['edit'], $parameters]);
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
            if ($entity instanceof Project)
                if (count($entity->getUsers())>0)
                {
                    $users='';
                    foreach ($entity->getUsers() as $key=>$value) {
                        $users.=$value->getUsername().' ,';
                    }
                    $this->addFlash('error', 'Suppression impossible ! Les utilisateurs : '.$users.' sont enregistrés sur ce Projet. Vider le projet de ses utilisateurs et rééssayez !');
                    return $this->redirectToReferrer();
                }
            try {
                $this->executeDynamicMethod('remove<EntityName>Entity', [$entity, $form]);
            } catch (ForeignKeyConstraintViolationException $e) {
                throw new EntityRemoveException(['entity_name' => $this->entity['name'], 'message' => $e->getMessage()]);
            }
            $this->addFlash('success', 'Le projet '.$entity->getStatuteType().' n°'.$entity->getId().' a été supprimé avec succès !');
            $this->dispatch(EasyAdminEvents::POST_REMOVE, ['entity' => $entity]);
        }

        $this->dispatch(EasyAdminEvents::POST_DELETE);

        return $this->redirectToReferrer();
    }

    protected function deleteBatchAction(array $ids): void
    {
        $class = $this->entity['class'];
        $primaryKey = $this->entity['primary_key_field_name'];
        $deleted='';
        $undeleted='';

        $entities = $this->em->getRepository($class)
            ->findBy([$primaryKey => $ids]);

        foreach ($entities as $entity) {
            if (count($entity->getUsers())>0) {
                $undeleted.=$entity->getStatuteType().' n°'.$entity->getId().' ,';
            }
            else {
                $deleted.=$entity->getStatuteType().' n°'.$entity->getId().' ,';
                $this->em->remove($entity);
            }
        }
        if ($undeleted!='') {
            $this->addFlash('warning', 'Les projets : '.$undeleted.'n\'ont pas été supprimés car ils possèdaient des utilisateurs !');
            $this->addFlash('success', 'Les projets : '.$deleted.'ont pu être supprimés !');
        }
         else $this->addFlash('success', 'Tous Les projets ont été supprimés avec succès !');
        $this->em->flush();
    }

    public function clearAction()
    {
        $id = $this->request->query->get('id');
        $entity = $this->em->getRepository(Project::class)->find($id);
        if(count($entity->getUsers())>0) {
            $users='';
            foreach ($entity->getUsers() as $key=>$user)
            {
                $entity->removeUser($user);
                $users.=$user->getUsername().' ,';
            }
            $this->em->flush();
            $this->addFlash('success', 'Les utilisateurs : '.$users.'ont été retirés du Projet '.$entity->getStatuteType().'. Vous pouvez maintenant supprimer le projet !');
        }
       else $this->addFlash('error', 'Le projet '.$entity->getStatuteType().' ne contient aucun utilisateur !');
        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));

    }

    public function toggleAction()
    {
        $id = $this->request->query->get('id');
        $entity = $this->em->getRepository(Project::class)->find($id);
        $before=$entity->getStatuteType();
        if ($entity->getStatute()==0)
            $entity->setStatute(1);
        else $entity->setStatute(0);
        $this->addFlash('success', 'Le projet est passé de '.$before.' a '.$entity->getStatuteType().' !');
        $this->em->flush();

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'list',
            'entity' => $this->request->query->get('entity'),
        ));
    }

    public function SeveralToggleBatchAction(array $ids)
    {
        $class = $this->entity['class'];
        $em = $this->getDoctrine()->getManagerForClass($class);
        foreach ($ids as $id) {
            $project = $em->find($class, $id);
            if ($project->getStatute()==0)
                $project->setStatute(1);
            else $project->setStatute(0);
        }
        $this->addFlash('success', 'Tous les projets ont changés de catégories !');
        $this->em->flush();
    }

    public function SeveralClearBatchAction(array $ids)
    {
        $class = $this->entity['class'];
        $em = $this->getDoctrine()->getManagerForClass($class);
        foreach ($ids as $id) {
            $projects='';
            $project = $em->find($class, $id);
            if (count($project->getUsers())>0) {
                foreach ($project->getUsers() as $key=>$user)
                {
                    $project->removeUser($user);
                }
            }
            else $projects.='n°'.$project->getId().' ,';
        }
        if ($projects!='')
            $this->addFlash('error', 'Les projets : '.$projects.' n\'ont pas pu être vidés car il ne possède pas d\'utilisateurs !');
        else $this->addFlash('success', 'Tous les projets ont été vidés de leurs utilisateurs !');
        $this->em->flush();
    }
}