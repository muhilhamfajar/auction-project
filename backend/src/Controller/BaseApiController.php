<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

abstract class BaseApiController extends AbstractController
{
    protected function serializeResponse($data, array $groups, int $status = 200, array $headers = []): JsonResponse
    {
        $serializer = $this->container->get('serializer');
        $context = ['groups' => $groups];
        $json = $serializer->serialize($data, 'json', $context);

        return new JsonResponse($json, $status, $headers, true);
    }


    protected function getFormErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $cause = $error->getCause();
            if ($cause instanceof TransformationFailedException) {
                $errors[$error->getOrigin()->getName()][] = $cause->getMessage();
            } else {
                $errors[$error->getOrigin()->getName()][] = $error->getMessage();
            }
        }
        return $errors;
    }
}
