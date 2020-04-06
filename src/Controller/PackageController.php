<?php

namespace App\Controller;

use App\Entity\PackageRequest;
use App\Exception\PackageNotFoundException;
use App\Model\PackageInformation;
use App\Repository\PackageRepository;
use App\Repository\PackageRequestRepository;
use App\Repository\ReleaseRepository;
use App\Service\AurService;
use App\Service\Collection;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PackageController extends AbstractController
{
    private PackageRepository $repository;
    private ReleaseRepository $releaseRepository;
    private PackageRequestRepository $requestRepository;
    private AurService $aurService;

    public function __construct(
        PackageRepository $packageRepository,
        ReleaseRepository $releaseRepository,
        PackageRequestRepository $requestRepository,
        AurService $aurService
    )
    {
        $this->repository = $packageRepository;
        $this->releaseRepository = $releaseRepository;
        $this->requestRepository = $requestRepository;
        $this->aurService = $aurService;
    }

    /**
     * @Route("/", name="packages", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('index.html.twig', ['releases' => $this->releaseRepository->findLastUpdated()]);
    }

    /**
     * @Route("/list", name="package_list", methods={"GET"})
     */
    public function list(): Response
    {
        return $this->render('list.html.twig', ['packages' => $this->repository->findAllSortedByName()]);
    }

    /**
     * @Route("/search", name="package_search", methods={"GET", "POST"})
     */
    public function search(Request $request): Response
    {
        $search = $request->get('package', false);
        $packages = null;

        if ($search && strlen($search) >= 3) {
            try
            {
                $packages = new Collection(
                    $this->aurService->searchPackages(strip_tags(htmlentities(htmlspecialchars($search))))
                );
                $packageNames = $packages->pluck('name')->toArray();
                $packagesInDatabase = (new Collection(
                    $this->repository->findExistingPackageNames($packageNames)
                ))->flatten();
                $packagesInRequest = (new Collection(
                    $this->requestRepository->findRequestedPackageNames($packageNames)
                ))->flatten();
                $packages->map(function (PackageInformation $package) use ($packagesInDatabase, $packagesInRequest) {
                    return $package
                        ->setInRepository($packagesInDatabase->contains($package->getName()))
                        ->setRequested($packagesInRequest->contains($package->getName()));

                });
            } catch (PackageNotFoundException $exception) {
                $packages = [];
            }
        }

        return $this->render('search.html.twig', ['packages' => $packages]);
    }

    /**
     * @Route("/suggest/{package}", name="package_suggest", methods={"GET"})
     */
    public function suggest(string $package, EntityManagerInterface $entityManager): Response
    {
        try
        {
            $this->aurService->getPackageInformation($package);
            $packageRequest = (new PackageRequest())
                ->setName($package)
                ->setCreatedAt(new Carbon());
            $entityManager->persist($packageRequest);
            $entityManager->flush();

            $this->addFlash('success', sprintf('Package «%s» has been successfully requested.', $package));
        } catch (\Exception $e) {
            $this->addFlash('danger', sprintf('Package «%s» does not exists.', $package));
        }

        return $this->redirectToRoute('packages');
    }
}
