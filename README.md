# Lightstreamer - Basic Chat Demo - PHP Adapter #
<!-- START DESCRIPTION lightstreamer-example-chat-adapter-php -->

The *Lightstreamer Basic Chat Demo* is a very simple chat application, based on [Lightstreamer](http://www.lightstreamer.com) for its real-time communication needs.

This project contains the source code and all the resources needed to deploy a [PHP](http://php.net/) port of the [Lightstreamer - Basic Chat Demo - Java Adapter](https://github.com/Weswit/Lightstreamer-example-Chat-adapter-java). In particular, a PHP-CLI version of the Adapter Set will be shown.

As an example of a client using this adapter, you may refer to the [Basic Chat Demo - HTML Client](https://github.com/Weswit/Lightstreamer-example-chat-client-javascript) and view the corresponding [Live Demo](http://demos.lightstreamer.com/ChatDemo/).

## Details

This project shows the use of PHP classes and interfaces already provided in the [Lightstreamer - "Hello World" Tutorial - PHP Adapter](https://github.com/Weswit/Lightstreamer-example-HelloWorld-adapter-php), such as IDataProvider, DataProviderServer, LiteralBasedProvider and MetadataProviderServer.

### Dig the Code

The code example is structured as follows:
* The `chat.php` file, which is the entry point of the example.
* The `autoload.php` file, which loads the required classes. 
* The "lightstreamer" hierarchy directory structure, containing all the PHP classes (a file for each class), which implement the ARI Protocol.

#### The Adapter Set Configuration
This Adapter Set is configured and will be referenced by the clients as `PROXY_PHPCHAT`.
As *Proxy Data Adapter* and *Proxy MetaData Adapter*, you may configure also the robust versions. The *Robust Proxy Data Adapter* and *Robust Proxy MetaData Adapter* have some recovery capabilities and avoid to terminate the Lightstreamer Server process, so it can handle the case in which a Remote Data Adapter is missing or fails, by suspending the data flow and trying to connect to a new Remote Data Adapter instance. Full details on the recovery behavior of the Robust Data Adapter are available as inline comments within the `DOCS-SDKs/adapter_remoting_infrastructure/doc/adapter_robust_conf_template/adapters.xml` file in your Lightstreamer Server installation.

The `adapters.xml` file for this demo should look like:

```xml
<?xml version="1.0"?>

<adapters_conf id="PROXY_PHPCHAT">
    <metadata_provider>
        <adapter_class>ROBUST_PROXY_FOR_REMOTE_ADAPTER</adapter_class>
        <classloader>log-enabled</classloader>
        <param name="request_reply_port">6663</param>
    </metadata_provider>
    
    <data_provider name="CHAT_ROOM">
        <adapter_class>ROBUST_PROXY_FOR_REMOTE_ADAPTER</adapter_class>
        <classloader>log-enabled</classloader>
        <param name="request_reply_port">6661</param>
        <param name="notify_port">6662</param>
    </data_provider>
</adapters_conf>
```

<!-- END DESCRIPTION lightstreamer-example-chat-adapter-php -->

## Install
If you want to install a version of this demo in your local Lightstreamer Server, follow these steps:
* Download *Lightstreamer Server* (Lightstreamer Server comes with a free non-expiring demo license for 20 connected users) from [Lightstreamer Download page](http://www.lightstreamer.com/download.htm), and install it, as explained in the `GETTING_STARTED.TXT` file in the installation home directory.
* Get the `deploy.zip` file installed from [releases](https://github.com/Weswit/Lightstreamer-example-Chat-adapter-php/releases) and unzip it, obtaining the `deployment` folder.
* Plug the Proxy Data Adapter into the Server: go to the `Deployment_LS` folder and copy the `ChatAdapterPHP` directory and all of its files to the `adapters` folder of your Lightstreamer Server installation.
* Alternatively, you may plug the *robust* versions of the Proxy Data Adapter: go to the `Deployment_LS(robust)` folder and copy the `ChatAdapterPHP` directory and all of its files into the `adapters` folder.
* Install the PHP Remote Adapter
 * Create a directory where to deploy the PHP Remote Adapter and let call it `Deployment_PHP_Remote_Adapter`.
 * Download all the PHP source files from this project and copy them into the `Deployment_PHP_Remote_Adapter` folder.
*  Launch Lightstreamer Server. The Server startup will complete only after a successful connection between the Proxy Data Adapter and the Remote Data Adapter.
* Launch the PHP Remote Adapter: open a command line to the `Deployment_PHP_Remote_Adapter` folder and launch:<BR/>
`> php chat.php --host localhost --metadata_rrport 6663 --data_rrport 6661 --data_notifport 6662`<BR/>
* IMPORTANT: The demo requires  the [pthreads](http://php.net/manual/en/intro.pthreads.php) module is installed into your php  environment. You can get detailed information on how to properly install the module [here](http://php.net/manual/en/pthreads.setup.php). The demo has been successfully tested on the following environments:
 * Windows 7 and 8, with PHP version [VC 11 Thread Safe for X86](http://windows.php.net/downloads/releases/archives/php-5.6.5-Win32-VC11-x86.zip) and pthreed module version [2.0.10-5.6-ts-vc11](http://windows.php.net/downloads/pecl/releases/pthreads/2.0.10/php_pthreads-2.0.10-5.6-ts-vc11-x86.zip)
 * Ubuntu Linux version 14.10, with PHP version 5.6.5 (compiled with the *--enable-maintainer-zts* flag) and pthread module version 2.0.10, installed as a pecl extension.
* Test the Adapter, launching the [Lightstreamer - Basic Chat Demo - HTML Client](https://github.com/Weswit/Lightstreamer-example-Chat-client-javascript) listed in [Clients Using This Adapter](https://github.com/Weswit/Lightstreamer-example-Chat-adapter-php#clients-using-this-adapter).
    * To make the [Lightstreamer - Basic Chat Demo - HTML Client](https://github.com/Weswit/Lightstreamer-example-Chat-client-javascript) front-end pages get data from the newly installed Adapter Set, you need to modify the front-end pages and set the required Adapter Set name to PROXY_PHPCHAT when creating the LightstreamerClient instance. So edit the `lsClient.js` file of the *Basic Chat Demo* front-end deployed under `Lightstreamer/pages/ChatDemo` and replace:<BR/>
`var lsClient = new LightstreamerClient(protocolToUse + "//localhost:" + portToUse, "CHAT");`<BR/>
with:<BR/>
`var lsClient = new LightstreamerClient(protocolToUse + "//localhost:" + portToUse, "PROXY_PHPCHAT");`<BR/>
(you don't need to reconfigure the Data Adapter name, as it is the same in both Adapter Sets).
    * As the referred Adapter Set has changed, make sure that the front-end no longer shares the Engine with other demos.
So a line like this:<BR/>
`lsClient.connectionSharing.enableSharing("ChatDemoCommonConnection", "ATTACH", "CREATE");`<BR/>
should become like this:<BR/>
`lsClient.connectionSharing.enableSharing("RemoteChatDemoConnection", "ATTACH", "CREATE");`
    * Open a browser window and go to: [http://localhost:8080/ChatDemo](http://localhost:8080/ChatDemo)

## See Also

* [Writing Remote PHP Adapters for Lightstreamer](http://blog.lightstreamer.com/2015/02/writing-remote-php-adapters-for.html)

### Clients Using This Adapter
<!-- START RELATED_ENTRIES -->

*    [Lightstreamer - Basic Chat Demo - HTML Client](https://github.com/Weswit/Lightstreamer-example-Chat-client-javascript)

<!-- END RELATED_ENTRIES -->

### Related Projects

*    [Lightstreamer - Basic Chat Demo - Java Adapter](https://github.com/Weswit/Lightstreamer-example-Chat-adapter-java)
*    [Lightstreamer - "Hello World" Tutorial - PHP Adapter](https://github.com/Weswit/Lightstreamer-example-HelloWorld-adapter-php)

## Lightstreamer Compatibility Notes

* Compatible with Lightstreamer SDK for Generic Adapters version 1.7 or newer.
* Compatible with Lightstreamer JavaScript Client Library version 6.0 or newer.
