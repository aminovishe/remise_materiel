<?php

namespace App\Controller;

use App\Service\Functions;
use App\Service\UpdateAppParams;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class GlobalController extends AbstractController
{
    protected $em;
    protected $updateAppParams;
    protected $functions;

    public function __construct(EntityManagerInterface $entityManager, UpdateAppParams $updateAppParams, Functions $functions)
    {
        $this->em = $entityManager;
        $this->updateAppParams = $updateAppParams;
        $this->functions = $functions;
    }

    /**
     * @Route("/password_powerlink", name="update_password_powerlink")
     *
     */
    public function updatePasswordPowerlink(Request $request)
    {
        $powerlinkParams = $this->updateAppParams->getParamByKey('powerlink');

        $form = $this->createFormBuilder(null)
            ->add('userId', TextType::class, [
                'label' => "ID utilisateur",
                'data' => $powerlinkParams['userId']
            ])
            ->add('password', PasswordType::class, [
                'label' => "Nouveau mot de passe",
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer',
                'attr' => [
                    'class' => 'btn btn-primary',
                    'onclick' => 'return showLoading();'
                ],
            ])
            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $params = [
                'userId' => $form->get('userId')->getData(),
                'password' => $form->get('password')->getData(),
            ];
            $this->updateAppParams->setParamByKey('powerlink', $params);

            return $this->redirectToRoute('home');
        }

        return $this->render('security/powerlink_params.html.twig', [
            'powerlinkParams' => $powerlinkParams,
            'form' => $form->createView(),
        ]);
    }
}
