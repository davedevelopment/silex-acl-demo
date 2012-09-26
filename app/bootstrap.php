<?php

/**
 * This is an attempt to show you how to bootstrap the symfony ACL system and 
 * use it with silex. I've used FQCN in most places to try and help show what 
 * the class might do
 */

use Silex\Application;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$app = new Application();
$app['debug'] = true;

/**
 * Doctrine
 */
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/../data/app.db',
    ),
));

$app['em'] = $app->share(function() use ($app) {
    $config = Setup::createAnnotationMetadataConfiguration(array(
        __DIR__ . '/../src/SilexAclDemo/Entity',
    ), true, __DIR__.'/../data/proxies');
    return EntityManager::create($app['db'], $config);
});

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/views',
));

/**
 * Standard security provider as documented at silex.sensiolabs.org
 */
$app->register(new Silex\Provider\SecurityServiceProvider());
$app['security.firewalls'] = array(
    'admin' => array(
        'pattern' => '^/',
        'http' => true,
        'users' => array(
            // raw password is foo
            'admin' => array('ROLE_ADMIN', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
            'davem' => array('ROLE_USER', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
            'benr' => array('ROLE_USER', '5FZ2Z8QIkA7UTZ4BYkoC+GsReLf569mSKDsfods6LYQ8t+a8EW9oaircfMpmaLbPBh4FOBiiFyLfuZmTSUwzZg=='),
        ),
    ),
);
$app['security.role_hierarchy'] = array(
    'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
);


/**
 * Symfony ACL
 */

// these five are simply database table names
$app['security.acl.dbal.class_table_name'] = 'acl_classes';
$app['security.acl.dbal.entry_table_name'] = 'acl_entries';
$app['security.acl.dbal.oid_table_name'] = 'acl_object_identities';
$app['security.acl.dbal.oid_ancestors_table_name'] = 'acl_object_identity_ancestors';
$app['security.acl.dbal.sid_table_name'] = 'acl_security_identities';

/**
 * This service is used to determine a unique id for whatever we are granting or 
 * checking permissions for, which I assume is usually a user or a role. Often 
 * abbreviated to SID
 *
 * The trust resolver is defined by the default SecurityServiceProvider, and is 
 * an Authentication trust resolver, which I translate to, can we trust them 
 * based on authentication. I don't know what other resolvers there might be.
 */
$app['security.acl.security_identity_retrieval_strategy'] = $app->share(function() use ($app) {
    return new Symfony\Component\Security\Acl\Domain\SecurityIdentityRetrievalStrategy(
        new Symfony\Component\Security\Core\Role\RoleHierarchy( $app['security.role_hierarchy']), 
        $app['security.trust_resolver'] // defined by security provider
    );
});

/**
 * This service is used to determine a unique id for an object (OID), in order to 
 * store ACLs against it. The only provided strategy expects the objects to 
 * implement the Symfony\Component\Security\Acl\Model\DomainObjectInterface, or 
 * have a getId method. 
 */
$app['security.acl.object_identity_retrieval_strategy'] = $app->share(function() use ($app) {
    return new Symfony\Component\Security\Acl\Domain\ObjectIdentityRetrievalStrategy();
});

/**
 * This does something important, but I honestly don't know what. I guess it 
 * actually makes the raw decisions, about whether a the permissions granted to 
 * a security identity, match the permission that we are checking
 */
$app['security.acl.permission_granting_strategy'] = $app->share(function() use ($app) {
    return new Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy();
});

/**
 * Provides the basic permission masks and their hierachy, e.g. OWNER can CREATE 
 * VIEW EDIT DELETE
 */
$app['security.acl.permission.map'] = $app->share(function() use ($app) {
    return new Symfony\Component\Security\Acl\Permission\BasicPermissionMap;
});

/**
 * This provides storage for the ACL, here we're using doctrine's DBAL
 */
$app['security.acl.provider'] = $app->share(function() use ($app) {
    $provider = new Symfony\Component\Security\Acl\Dbal\MutableAclProvider(
        $app['db'],
        $app['security.acl.permission_granting_strategy'], 
        array(
            'class_table_name' => $app['security.acl.dbal.class_table_name'],
            'sid_table_name' => $app['security.acl.dbal.sid_table_name'],
            'oid_table_name' => $app['security.acl.dbal.oid_table_name'],
            'oid_ancestors_table_name' => $app['security.acl.dbal.oid_ancestors_table_name'],
            'entry_table_name' => $app['security.acl.dbal.entry_table_name'],
        )
    );

    return $provider;
});

/**
 * A voter votes on things :)
 *
 * My understanding is a voter can use it's knowledge to influence (vote) a 
 * decision made an AccessDecisionManager. The stock silex 
 * SecurityServiceProvider already includes voters that vote based on 
 * Authentication, Roles and Role Hierachy. We'll add this one in a minute.
 *
 * I think it uses:
 *
 * The provider to retrieve ACL from the datasource
 * The ObjectIdentityRetrievalStrategy to determine which object we checking 
 * access to
 * The SecurityIdentityRetrievalStrategy to determine who or what we are voting 
 * to allow or deny access to
 * The permissions map to identify what we permission we are asking for
 */
$app['security.acl.voter.basic_permissions'] = $app->share(function() use ($app) {
    return new Symfony\Component\Security\Acl\Voter\AclVoter(
        $app['security.acl.provider'],
        $app['security.acl.object_identity_retrieval_strategy'],
        $app['security.acl.security_identity_retrieval_strategy'],
        $app['security.acl.permission.map']
    );
});

/**
 * Use the extend method to add our ACL voter to the existing voters, defined by 
 * the default SecurityServiceProvider
 */
$app['security.voters'] = $app->share($app->extend('security.voters', function($voters) use ($app) {
    $voters[] = $app['security.acl.voter.basic_permissions'];
    return $voters;
}));


$app->match("/", function() use ($app) {

    if ($app['request']->getMethod() == 'POST') {

        $currentUser = $app['security']->getToken()->getUser();

        $message = new SilexAclDemo\Entity\Message(
            $app['request']->request->get('content'),
            $currentUser->getUsername()
        );

        $app['em']->persist($message);
        $app['em']->flush();

        $oid = Symfony\Component\Security\Acl\Domain\ObjectIdentity::fromDomainObject($message);
        $acl = $app['security.acl.provider']->createAcl($oid);

        // the current user is the owner
        $sid = Symfony\Component\Security\Acl\Domain\UserSecurityIdentity::fromAccount($currentUser);
        $acl->insertObjectAce($sid, Symfony\Component\Security\Acl\Permission\MaskBuilder::MASK_OWNER);

        // anyone with the admin role can do what they like
        $adminRole = new Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity("ROLE_ADMIN");
        $acl->insertObjectAce($adminRole, Symfony\Component\Security\Acl\Permission\MaskBuilder::MASK_MASTER);

        $app['security.acl.provider']->updateAcl($acl);

        return $app->redirect("/");
    }

    return $app['twig']->render("index.html.twig", array(
        'messages' => $app['em']->getRepository("SilexAclDemo\Entity\Message")->findAll(),
    ));

})->method("GET|POST");

$app->delete("/{id}", function($id) use ($app) {

    $message = $app['em']->find("SilexAclDemo\Entity\Message", $id);

    if (!$message) {
        $app->abort(404, "Message not found");
    }

    if (!$app['security']->isGranted('DELETE', $message)) {
        $app->abort(403, "Forbidden");
    }

    $oid = Symfony\Component\Security\Acl\Domain\ObjectIdentity::fromDomainObject($message);
    $app['security.acl.provider']->deleteAcl($oid);

    $app['em']->remove($message);
    $app['em']->flush();
    return $app->redirect("/");

});

return $app;
