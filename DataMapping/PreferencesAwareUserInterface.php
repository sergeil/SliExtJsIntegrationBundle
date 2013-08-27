<?php

namespace Sli\ExtJsIntegrationBundle\DataMapping;

/**
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
interface PreferencesAwareUserInterface
{
    const SETTINGS_DATE_FORMAT = 'dateFormat';
    const SETTINGS_DATETIME_FORMAT = 'datetimeFormat';
    const SETTINGS_MONTH_FORMAT = 'monthFormat';

    /**
     * @return array
     */
    public function getPreferences();
}
