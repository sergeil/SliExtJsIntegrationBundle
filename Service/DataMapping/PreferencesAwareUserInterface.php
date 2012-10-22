<?php

namespace Sli\ExtJsIntegrationBundle\Service\DataMapping;

/**
 * @copyright 2012 Modera Foundation
 * @author Sergei Lissovski <sergei.lissovski@modera.net>
 */
interface PreferencesAwareUserInterface
{
    const SETTINGS_DATE_FORMAT = 'dateFormat';
    const SETTINGS_DATETIME_FORMAT = 'datetimeFormat';

    /**
     * @return array
     */
    public function getPreferences();
}
