<?php

namespace Sli\ExtJsIntegrationBundle\Response;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Sli\ExtJsIntegrationBundle\Response\EscapingDecisionManagerInterface;

/**
 * To activate this filter you need to define a service in DIC with the following configuration
 * - tag name: kernel.event_listener
 * - event: kernel.response
 * - method: onKernelResponse
 *
 * For example:
 *
 *  <service id="ts.core.escaping_decision_manager"
 *           class="Ts\CoreBundle\Security\EscapingDecisionManager"
 *           public="false">
 *  </service>
 *
 *  <service id="ts.core.response_escaping_filter"
 *           class="Sli\ExtJsIntegrationBundle\Response\ResponseEscapingFilter">
 *      <argument type="service" id="ts.core.escaping_decision_manager" />
 *      <tag name="kernel.event_listener" event="kernel.response" method="onKernelResponse" />
 *  </service>
 *
 * @author Sergei Lissovski <sergei.lissovski@gmail.com>
 */
class ResponseEscapingFilter
{
    private $escapingDecisionManager;

    public function __construct(EscapingDecisionManagerInterface $escapingDecisionManager)
    {
        $this->escapingDecisionManager = $escapingDecisionManager;
    }

    /**
     * Override this method in your subclass if some different escaping logic must be applied.
     *
     * @param $string
     * @return string
     */
    protected function escapeString($string)
    {
        return htmlspecialchars($string);
    }

    private function escapeRecursively(array $input)
    {
        foreach ($input as $key=>$value) {
            $input[$key] = is_array($value) ? $this->escapeRecursively($value) : $this->escapeString($value);
        }
        return $input;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$this->escapingDecisionManager->isEscapingNeeded($event->getRequest(), $event->getResponse())) {
            return;
        }

        $response = $event->getResponse();

        $decoded = json_decode($response->getContent(), true);
        if (null !== $decoded && is_array($decoded)) {
            $response->setContent(json_encode($this->escapeRecursively($decoded)));
        } else {
            $response->setContent($this->escapeString($response->getContent()));
        }
    }

    static public function clazz()
    {
        return get_called_class();
    }
}
