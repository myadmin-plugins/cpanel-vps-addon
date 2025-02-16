<?php

namespace Detain\MyAdminVpsCpanel;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminVpsCpanel
 */
class Plugin
{
    public static $name = 'CPanel VPS Addon';
    public static $description = 'Allows selling of CPanel Licenses as VPS Addon.  cPanel is an online (Linux-based) web hosting control panel that provides a graphical interface and automation tools designed to simplify the process of hosting a web site. cPanel utilizes a 3 tier structure that provides capabilities for administrators, resellers, and end-user website owners to control the various aspects of website and server administration through a standard web browser.  More info at https://cpanel.com/';
    public static $help = '';
    public static $module = 'vps';
    public static $type = 'addon';

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getHooks()
    {
        return [
            'function.requirements' => [__CLASS__, 'getRequirements'],
            self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
            self::$module.'.settings' => [__CLASS__, 'getSettings']
        ];
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getRequirements(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
        $loader->add_page_requirement('vps_add_cpanel', '/../vendor/detain/myadmin-cpanel-vps-addon/src/vps_add_cpanel.php');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getAddon(GenericEvent $event)
    {
        $serviceOrder = $event->getSubject();
        function_requirements('class.AddonHandler');
        $addon = new \AddonHandler();
        $addon->setModule(self::$module)
            ->set_text('CPanel')
            ->set_text_match('CPanel (.*) Accounts')
            ->set_cost(VPS_CPANEL_COST)
            ->set_require_ip(true)
            ->setEnable([__CLASS__, 'doEnable'])
            //->setVerify([__CLASS__, 'doEnable'])
            ->setDisable([__CLASS__, 'doDisable'])
            ->register();
        $serviceOrder->addAddon($addon);
    }

    /**
     * @param \ServiceHandler $serviceOrder
     * @param                $repeatInvoiceId
     * @param bool           $regexMatch
     */
    public static function doEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
    {
        $serviceInfo = $serviceOrder->getServiceInfo();
        $serviceTypes = run_event('get_service_types', false, self::$module);
        $settings = get_module_settings(self::$module);
        require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
        myadmin_log(self::$module, 'info', self::$name.' Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
        function_requirements('get_cpanel_license_data_by_ip');
        $serviceExtra = get_cpanel_license_data_by_ip($serviceInfo[$settings['PREFIX'].'_ip']);
        // check if activated,if not then activate cpanel license
        if (($serviceExtra === false || $serviceExtra['valid'] != 1) && $serviceInfo[$settings['PREFIX'].'_ip'] != '') {
            function_requirements('activate_cpanel');
            activate_cpanel($serviceInfo[$settings['PREFIX'].'_ip'], 31369);
            $GLOBALS['tf']->history->add($settings['TABLE'], 'add_cpanel', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
        }
    }

    /**
     * @param \ServiceHandler $serviceOrder
     * @param                $repeatInvoiceId
     * @param bool           $regexMatch
     */
    public static function doDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
    {
        $serviceInfo = $serviceOrder->getServiceInfo();
        $settings = get_module_settings(self::$module);
        require_once __DIR__.'/../../../../include/licenses/license.functions.inc.php';
        myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
        function_requirements('get_cpanel_license_data_by_ip');
        $serviceExtra = get_cpanel_license_data_by_ip($serviceInfo[$settings['PREFIX'].'_ip']);
        // check if activated,if so then deactivate cpanel license
        if ($serviceExtra !== false && $serviceExtra['valid'] == 1 && $serviceInfo[$settings['PREFIX'].'_ip'] != '') {
            function_requirements('deactivate_cpanel');
            deactivate_cpanel($serviceInfo[$settings['PREFIX'].'_ip']);
            $GLOBALS['tf']->history->add($settings['TABLE'], 'del_cpanel', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_ip'], $serviceInfo[$settings['PREFIX'].'_custid']);
            add_output(self::$name.' Canceled');
            $email = $settings['TBLNAME'].' ID: '.$serviceInfo[$settings['PREFIX'].'_id'].'<br>'.$settings['TBLNAME'].' Hostname: '.$serviceInfo[$settings['PREFIX'].'_hostname'].'<br>Repeat Invoice: '.$repeatInvoiceId.'<br>Description: '.self::$name.'<br>';
            $subject = $settings['TBLNAME'].' '.$serviceInfo[$settings['PREFIX'].'_id'].' Canceled '.self::$name;
            (new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/vps_cpanel_canceled.tpl');
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
        $settings->setTarget('module');
        $settings->add_text_setting(self::$module, _('Addon Costs'), 'vps_cpanel_cost', _('VPS CPanel License'), _('This is the cost for purchasing a cpanel license on top of a VPS.'), $settings->get_setting('VPS_CPANEL_COST'));
        $settings->setTarget('global');
    }
}
