<?php

namespace App\DataFixtures;

use App\Entity\Alert;
use App\Entity\Article;
use App\Entity\Availability;
use App\Entity\Donation;
use App\Entity\Event;
use App\Entity\PreOrder;
use App\Entity\PreOrderItem;
use App\Entity\Role;
use App\Entity\User;
use App\Enum\AlertStatus;
use App\Enum\BirdhouseDiameter;
use App\Enum\DonationStatus;
use App\Enum\DonorType;
use App\Enum\PreOrderStatus;
use App\Service\Donation\DonationPdfService;
use App\Service\Shop\PreOrderPdfService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Jeu de données de démonstration utilisé pour :
 * - les tests de développement ;
 * - les démonstrations ;
 * - les recettes fonctionnelles.
 *
 * Les données générées permettent de simuler :
 * - les utilisateurs ;
 * - les rôles ;
 * - les disponibilités ;
 * - les dons ;
 * - les précommandes ;
 * - les signalements ;
 * - les actualités.
 */
class AppFixtures extends Fixture
{

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private DonationPdfService          $donationPdfService,
        private PreOrderPdfService          $preOrderPdfService,
    )
    {
    }

    /**
     * Chargement des données dans un ordre précis
     * afin de respecter les dépendances entre entités.
     */
    public function load(ObjectManager $manager): void
    {
        $this->addAvailability($manager);
        $this->addRoles($manager);
        $manager->flush();

        $this->addUsers($manager);
        $manager->flush();

        $this->addArticles($manager);
        $manager->flush();

        $this->addDonations($manager);
        $manager->flush();

        $this->addPreOrders($manager);
        $this->addAlert($manager);
        $this->addEvents($manager);
        $manager->flush();

    }

    /**
     * Création des utilisateurs de démonstration.
     *
     * Certains comptes disposent du rôle administrateur
     * afin de faciliter les tests de l'interface d'administration.
     */
    public function addUsers(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $usersData = [
            ['firstname' => 'Adrien', 'lastname' => 'Le Clech', 'email' => 'adrien@leclech.com', 'roles' => ['ROLE_ADMIN']],
            ['firstname' => 'Adrien', 'lastname' => 'Quintard', 'email' => 'adrien@quintard.com', 'roles' => ['ROLE_ADMIN']],
            ['firstname' => 'Laurent', 'lastname' => 'Berthélémy', 'email' => 'laurent@berthelemy.com', 'roles' => ['ROLE_ADMIN']],
            ['firstname' => 'Eni', 'lastname' => 'Stage', 'email' => 'enistage@enistage.com', 'roles' => ['ROLE_USER']],
        ];
        $availabilities = $manager->getRepository(Availability::class)->findAll();
        $roles = $manager->getRepository(Role::class)->findAll();

        foreach ($usersData as $userData) {
            $user = new User();
            $user->setFirstname($userData['firstname']);
            $user->setLastname($userData['lastname']);
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            // Mot de passe commun utilisé uniquement pour les données de test.
            $user->setPassword($this->passwordHasher->hashPassword($user, '123456'));
            $user->setPhone($faker->phoneNumber);
            $user->setAddress($faker->address);
            $user->setCity($faker->city);
            $user->setzipCode($faker->postcode);
            $user->setCreationDate($faker->dateTime());
            $user->setUserRole($faker->randomElement($roles));
            $user->setBirthday($faker->dateTime);
            $user->setActif(true);
            $user->setIsVerified(true);

            // Attribution aléatoire de disponibilités à chaque utilisateur.
            $randomAvailabilities = $faker->randomElements(
                $availabilities,
                $faker->numberBetween(1, count($availabilities))
            );

            foreach ($randomAvailabilities as $availability) {
                $user->addAvailability($availability);
            }
            $manager->persist($user);
        }
    }

    public function addRoles(ObjectManager $manager): void
    {
        $rolesData = ['Président', 'Bénévole', 'Utilisateur', 'Agriculteur', 'Pilote de drone'];
        foreach ($rolesData as $roleData) {
            $newRole = new Role();
            $newRole->setName($roleData);
            $manager->persist($newRole);
        }
    }

    public function addAvailability(ObjectManager $manager): void
    {
        $availabilitysData = ['matin', 'soir', 'journée', 'semaine', 'week-end', 'toutes'];
        foreach ($availabilitysData as $availabilityData) {
            $newAvailability = new Availability();
            $newAvailability->setLabel($availabilityData);
            $manager->persist($newAvailability);
        }
    }

    public function addArticles(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $user = $manager->getRepository(User::class)->findOneBy([
            'email' => 'adrien@leclech.com'
        ]);
        $articlesData = [
            ['name' => 'Nichoir demi rond', 'photo' => 'nichoirQuartDeRond.png', 'price' => '14.99'],
            ['name' => 'Nichoir à graine', 'photo' => 'nichoirGraine.png', 'price' => '14.99'],
            ['name' => 'Nichoir mural', 'photo' => 'nichoirMuralVertical.png', 'price' => '14.99'],
            ['name' => 'Nichoir vertical', 'photo' => 'nichoirVertical.png', 'price' => '14.99'],
        ];
        foreach ($articlesData as $articleData) {
            $newArticle = new Article();
            $newArticle->setName($articleData['name']);
            $newArticle->setImage($articleData['photo']);
            $newArticle->setPrice($articleData['price']);
            $newArticle->setRequiresDiameter(true);
            $newArticle->setDescription($faker->text(200));
            $newArticle->setCreationDate(\DateTimeImmutable::createFromMutable($faker->dateTime()));
            $newArticle->setUser($user);
            $manager->persist($newArticle);
        }
    }

    /**
     * Génération de dons fictifs.
     *
     * Les dons peuvent être réalisés :
     * - par des particuliers ;
     * - par des entreprises.
     */
    public function addDonations(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $faker = Factory::create('fr_FR');

        foreach ($users as $user) {
            for ($i = 0; $i < rand(1, 10); $i++) {
                $donation = new Donation();

                // Environ 35 % des dons sont réalisés par des entreprises.
                $isCompanyDonation = $faker->boolean(35);

                $donation->setUser($user);
                $donation->setAmount($faker->randomFloat(2, 5, 400));
                $donation->setDonationDate($faker->dateTime());
                $donation->setStatus(DonationStatus::DONATION_VALIDEE);

                if ($isCompanyDonation) {
                    $donation->setDonorType(DonorType::ENTREPRISE);
                    $donation->setCompanyName($faker->company());
                    $donation->setCompanySiret($faker->numerify('##############'));

                    $donation->setFirstname($faker->firstName());
                    $donation->setLastname($faker->lastName());
                    $donation->setEmail($faker->companyEmail());

                    $donation->setAddress($faker->streetAddress());
                    $donation->setCity($faker->city());
                    $donation->setZipcode($faker->postcode());
                } else {
                    $donation->setDonorType(DonorType::PARTICULIER);

                    $donation->setFirstname($user->getFirstname());
                    $donation->setLastname($user->getLastname());
                    $donation->setEmail($user->getEmail());
                    $donation->setAddress($user->getAddress());
                    $donation->setCity($user->getCity());
                    $donation->setZipcode($user->getZipcode());
                }

                $manager->persist($donation);
                $manager->flush();

                // Génération automatique du reçu fiscal associé au don.
                $this->donationPdfService->generateFiscalReceipt($donation);

                $manager->flush();
            }
        }
    }

    /**
     * Génération de précommandes fictives.
     *
     * Chaque précommande contient :
     * - un ou plusieurs articles ;
     * - une quantité ;
     * - un diamètre de nichoir ;
     * - un statut métier.
     */
    public function addPreOrders(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $articles = $manager->getRepository(Article::class)->findAll();
        $statuses = PreOrderStatus::cases();
        $diameters = BirdhouseDiameter::cases();
        $faker = Factory::create('fr_FR');

        foreach ($users as $user) {

            for ($i = 0; $i < rand(1, 5); $i++) {
                $preOrder = new PreOrder();
                $status = $faker->randomElement($statuses);
                $preOrder->setUser($user);
                // Attribution d'un statut aléatoire à la précommande.
                $preOrder->setStatus($status);
                $preOrder->setPreOrderDate(\DateTimeImmutable::createFromMutable($faker->dateTime()));
                $totalAmount = 0;
                $randomArticles = $faker->randomElements($articles, $faker->numberBetween(1, count($articles)));

                foreach ($randomArticles as $article) {
                    $quantity = $faker->numberBetween(1, 5);
                    $unitPrice = (float)$article->getPrice();
                    $totalPrice = $unitPrice * $quantity;
                    $preOrderItem = new PreOrderItem();
                    $preOrderItem->setArticle($article);
                    $preOrderItem->setPreOrder($preOrder);
                    $preOrderItem->setQuantity($quantity);
                    $preOrderItem->setDiameter($faker->randomElement($diameters));
                    $preOrderItem->setUnitPrice((string)$unitPrice);
                    $preOrderItem->setTotalPrice((string)$totalPrice);
                    $preOrder->addPreOrderItem($preOrderItem);
                    $totalAmount += $totalPrice;
                }

                $preOrder->setTotalAmount((string)$totalAmount);
                // Une précommande validée est considérée comme traitée par l'association.
                if (
                    $status === PreOrderStatus::VALIDEE
                    || $status === PreOrderStatus::EN_ATTENTE_PAIEMENT
                    || $status === PreOrderStatus::PAYEE
                ) {
                    $preOrder->setValidatedAt(new \DateTimeImmutable());
                }
                // Les précommandes payées disposent également d'une facture PDF.
                if ($status === PreOrderStatus::PAYEE) {
                    $preOrder->setPaidAt(new \DateTimeImmutable());
                    $preOrder->setHelloassoOrderId((string)$faker->numberBetween(100000, 999999));

                    $manager->persist($preOrder);
                    $manager->flush();

                    $this->preOrderPdfService->generateInvoice($preOrder);

                    $manager->flush();

                    continue;
                }
                $manager->persist($preOrder);
            }
        }
    }

    /**
     * Génération de signalements fictifs.
     *
     * Les signalements permettent de simuler les demandes
     * d'intervention effectuées par les utilisateurs.
     */
    public function addAlert(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $statues = AlertStatus::cases();
        $faker = Factory::create('fr_FR');
        // Types d'animaux pouvant faire l'objet d'un signalement.
        $typeData = ['Faons', 'Oiseau'];

        $cultureTypeData = ['Blé', 'Colza', 'Herbe', 'Maïs', 'Orge', 'Sorgho'];

        foreach ($users as $user) {
            for ($i = 0; $i < rand(1, 5); $i++) {
                $alert = new Alert();
                $alert->setType($faker->randomElement($typeData));
                $alert->setCultureType($faker->randomElement($cultureTypeData));
                $alert->setSurface($faker->numberBetween(1, 100));
                $alert->setCreationDate($faker->dateTime());
                $alert->setInterventionDate($faker->dateTime());
                $alert->setLocalisation($faker->city());
                $alert->setStatus($faker->randomElement($statues));
                $alert->setGpsLatitude($faker->latitude());
                $alert->setGpsLongitude($faker->longitude());
                $alert->setDescription($faker->text(200));
                $alert->setImage($faker->imageUrl());
                $alert->setUser($user);

                $manager->persist($alert);
            }
        }
    }

    /**
     * Génération des actualités de démonstration.
     */
    public function addEvents(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $faker = Factory::create('fr_FR');

        foreach ($users as $user) {
            for ($i = 0; $i < rand(1, 5); $i++) {
                $event = new Event();
                $event->setName($faker->word());
                $event->setLocalisation($faker->city());
                $event->setDescription($faker->text(200));
                $event->setImage($faker->imageUrl());
                $event->setNbParticipant($faker->numberBetween(1, 50));
                $event->setEventDate($faker->dateTime());
                $event->setUser($user);
                // Les actualités générées sont directement publiées.
                $event->setIsPublished(true);
                $manager->persist($event);
            }
        }
    }
}
