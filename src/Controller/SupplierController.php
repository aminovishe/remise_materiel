<?php

namespace App\Controller;

use App\Entity\Supplier;
use App\Form\SupplierType;
use Doctrine\ORM\EntityManagerInterface;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/supplier")
 */
class SupplierController extends AbstractController
{
    protected $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @Route("/list", name="supplier_list")
     */
    public function list(Request $request, DataTableFactory $dataTableFactory)
    {
        $table = $dataTableFactory->create([])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Supplier::class
            ])
            ->add('ref', TextColumn::class, [
                'label' => 'Num. Fournisseur'
            ])
            ->add('nom', TextColumn::class, [
                'label' => 'Fournisseur'
            ])
            ->add('address', TextColumn::class, [
                'label' => 'Adresse postale',
            ])
            ->add('postalCode', TextColumn::class, [
                'label' => 'Code postale',
            ])
            ->add('city', TextColumn::class, [
                'label' => 'Ville',
            ])
            ->add('country', TextColumn::class, [
                'label' => 'Pays',
            ])
            ->add('buttons', TextColumn::class, [
                'label' => 'Action',
                'raw' => true,
                'render' => function ($value,$entity) {
                    $buttons = '<a href="'. $this->generateUrl('supplier_post_edit', ['id' => $entity->getId()]) .'" class="btn btn-sm btn-warning btn-icon ml-3" title="Modifier"><i class="la la-edit"></i></a>';
                    return sprintf($buttons);
                }
            ])
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('supplier/list.html.twig', [
            'datatable' => $table
        ]);
    }

    /**
     * @Route("/post_edit/{id}", defaults={"id" = null}, name="supplier_post_edit")
     */
    public function postEdit(Request $request, Supplier $supplier = null)
    {
        if (!is_object($supplier)){
            $supplier = new Supplier();
        }
        $form = $this->createForm(SupplierType::class, $supplier)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($supplier);
            $this->em->flush();
            return $this->redirectToRoute('supplier_list');
        }

        return $this->render('supplier/post_edit.html.twig',[
            'supplier' => $supplier,
            'form' => $form->createView(),
        ]);
    }
}
