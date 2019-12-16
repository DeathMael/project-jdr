<?php


namespace App\Controller;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use EasyCorp\Bundle\EasyAdminBundle\Event\EasyAdminEvents;
use EasyCorp\Bundle\EasyAdminBundle\Exception\EntityRemoveException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectBackendController extends EasyAdminController
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
            $users='';
            if ($entity instanceof Project)
                foreach ($entity->getUsers() as $key => $value) {
                    if ($value->getProject()!=null)
                        $users.=$value->getUsername().' ,';
                    $value->setProject($entity);
                }
            $this->addFlash('success', 'Le projet '.$entity->getStatuteType() . ' a été ajouté avec succès !');
            if ($users!='')
                $this->addFlash('warning', 'Les utilisateurs : '.$users.' ont quittés leurs projets respectifs !');
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
            return new Response((int)$newValue);
        }

        $fields = $this->entity['edit']['fields'];

        $editForm = $this->executeDynamicMethod('create<EntityName>EditForm', [$entity, $fields]);
        $deleteForm = $this->createDeleteForm($this->entity['name'], $id);

        $editForm->handleRequest($this->request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if ($entity instanceof Project) {
                $em = $this->getDoctrine()->getManagerForClass(User::class);
                $ids = $this->getDoctrine()->getRepository(User::class)->findAll();
                foreach ($ids as $id) {
                    $user = $em->find(User::class, $id);
                    $user->setProject(null);
                }
                $class = $this->entity['class'];
                $em = $this->getDoctrine()->getManagerForClass($class);
                $ids = $this->getDoctrine()->getRepository(Project::class)->findAll();
                foreach ($ids as $id) {
                    $project = $em->find($class, $id);
                    if ($project instanceof Project && $project!=$entity)
                        foreach ($project->getUsers() as $key=>$value) {
                            $value->setProject($project);
                        }
                }
                $users='';
                foreach ($entity->getUsers() as $key => $value) {
                    if ($value->getProject()!=null)
                        $users.=$value->getUsername().' ,';
                    $value->setProject($entity);
                }
                $this->addFlash('success', 'Le projet ' . $entity->getStatuteType().' n°'.$entity->getId() . ' a été modifié avec succcès !');
                if ($users!='')
                    $this->addFlash('warning', 'Les utilisateurs : '.$users.' ont quittés leurs projets respectifs !');
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
                        $value->setProject(null);
                    }
                    $this->addFlash('warning', 'Les utilisateurs : '.$users.' étaient enregistrés sur ce projet, ils n\'appartiennent plus à aucun projet !');
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
        $empty='';
        $full='';

        $entities = $this->em->getRepository($class)
            ->findBy([$primaryKey => $ids]);

        foreach ($entities as $entity) {
            if (count($entity->getUsers())>0) {
                $full.=$entity->getTitle().', ';
                if ($entity instanceof Project)
                    foreach ($entity->getUsers() as $key=>$value)
                        $value->setProject(null);
            }
            else {
                $empty.=$entity->getStatuteType().' n°'.$entity->getId().', ';
            }
            $this->em->remove($entity);
        }
        if ($full!='')
            $this->addFlash('warning', 'Les projets : '.$full.' possèdaient des utilisateurs, ces utilisateurs ne possèdent désormais plus de projet !');
        $this->addFlash('success', 'Tous Les projets sélectionnés ont été supprimés avec succès !');
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
            $this->addFlash('success', 'Les utilisateurs : '.$users.'ont été retirés du Projet '.$entity->getStatuteType().' !');
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
        $this->addFlash('success', 'Tous les projets sélectionnés ont changé de catégories !');
        $this->em->flush();
    }

    public function SeveralClearBatchAction(array $ids)
    {
        $class = $this->entity['class'];
        $em = $this->getDoctrine()->getManagerForClass($class);
        $uncleared='';
        $cleared='';
        foreach ($ids as $id) {
            $project = $em->find($class, $id);
            if (count($project->getUsers())>0) {
                foreach ($project->getUsers() as $key=>$user)
                {
                    $project->removeUser($user);
                }
            $cleared.='n°'.$project->getId().' ,';
            }
            else $uncleared.='n°'.$project->getId().' ,';
        }
        if ($uncleared!='') {
            $this->addFlash('error', 'Les projets : '.$uncleared.' n\'ont pas pu être vidé car il ne possède pas d\'utilisateurs !');
            $this->addFlash('success', 'Les projets : '.$cleared.' ont pu être vidé !');
        }

        else $this->addFlash('success', 'Tous les projets sélectionnés ont été vidé de leurs utilisateurs !');
        $this->em->flush();
    }
}