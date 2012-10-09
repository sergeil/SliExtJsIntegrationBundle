The bundle will contain some utility classes that prove useful when you use ExtJs library on the client-side.

Bundle depends on https://github.com/sergeil/SliAuxBundle bundle.

Installation
============

1. Update your ``app/autoload.php``:

    Register a namespace mapping:

    ```
    'Sli' => __DIR__.'/../vendor/bundles',
    ```

    To the bottom of file add the following:

    ```
    AnnotationRegistry::registerFile(
        __DIR__.'/../vendor/bundles/Sli/ExtJsIntegrationBundle/Service/SortingFieldAnnotations.php'
    );
    ```

2. In your ``/deps`` file add following dependencies:

    ```
    [SliAuxBundle]
        git=http://github.com/sergeil/SliAuxBundle.git
        target=/bundles/Sli/AuxBundle

    [SliExtJsIntegrationBundle]
        git=http://github.com/sergeil/SliExtJsIntegrationBundle.git
        target=/bundles/Sli/ExtJsIntegrationBundle
    ```

3. Add bundles to your ``/app/AppKernel``:
    ```
    new Sli\ExtJsIntegrationBundle\SliExtJsIntegrationBundle(),
    new Sli\AuxBundle\SliAuxBundle(),
    ```

What's inside
=============
For now, there's not much yet:

 * Service\ExtjsQueryBuilder -- Builds proper Doctrine queries for your ``Ext.data.Store`` on client-side. Makes it possible
                                to easily leverage 'remoteFilter', 'remoteSort' as well as pagination. Also, the class is smart
                                enough to build proper queries when you need to sort(ORDER BY) an associated entity

Please read inline phpDoc for more information how to use classes