<?php
namespace AppBundle\Twig;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;
use AppBundle\Service\DateFormatter;

class ComponentsExtension extends \Twig_Extension
{
    /**
     * @var \Twig_Environment
     */
    private $environment;
    
    /**
     * @var TranslatorInterface
     */
    private $translator;
    
    /**
     * @var array
     */
    private $params;
    
    /**
     * @param array $params
     */
    public function __construct(TranslatorInterface $translator, $params)
    {
        $this->translator = $translator;
        $this->params = $params;
    }
    
    public function initRuntime(\Twig_Environment $environment)
    {
        parent::initRuntime($environment);
        $this->environment = $environment;
    }
    
    public function getFunctions()
    {
        return [
            'progress_bar' => new \Twig_Function_Method($this, 'progressBar'),
            'accordionLinks' => new \Twig_Function_Method($this, 'renderAccordionLinks'),
        ];
    }
    
    public function getFilters()
    {
        return [
            'country_name' => new \Twig_SimpleFilter('country_name', function($value) {
                return \Symfony\Component\Intl\Intl::getRegionBundle()->getCountryName($value);
            }),
           'last_loggedin_date_formatter' => new \Twig_SimpleFilter('last_loggedin_date_formatter', function($value) {
               if ($value instanceof \DateTime)  {
                   return $this->formatTimeDifference([
                       'from' => $value, 
                       'to' => new \DateTime(),
                       'translationDomain' => 'common',
                       'translationPrefix' => 'lastLoggedIn.',
                       'defaultDateFormat' => 'd F Y'
                   ]);
               }
            }),
            'pad_day_month' => new \Twig_SimpleFilter('pad_day_month', function($value) {
               if ($value && (int)$value >= 1 &&  (int)$value <=9) {
                   return "0" . (int)$value;
               }
               
               return $value;
            }),
            // convert 'Very Random "string" !!" into "very-random-string"
            'behat_namify' => new \Twig_SimpleFilter('behat_namify', function($string) {
                
                $string = preg_replace ('/[^\s_\-a-zA-Z0-9]/u', '', $string); // remove unneeded chars
                $string = str_replace ('_', ' ', $string);             // treat underscores as spaces
                $string = preg_replace ('/^\s+|\s+$/', '', $string);   // trim leading/trailing spaces
                $string = preg_replace ('/[-\s]+/', '-', $string);     // convert spaces to hyphens
                $string = strtolower ($string);                        // convert to lowercase
               
               return $string;
            })
        ];
    }
    
    
    /**
     * @param \DateTime from
     * @param \DateTime to
     * @param string translationPrefix
     * @param string defaultDateFormat e.g. d F Y
     * @param string translationDomain
     * 
     * @return string formatted interval
     */
    public function formatTimeDifference(array $options) {
        $from = $options['from'];
        $to = $options['to'];
        $translationPrefix = $options['translationPrefix'];
        $defaultDateFormat = $options['defaultDateFormat'];
        $translationDomain = $options['translationDomain'];
        
        $secondsDiff = $to->getTimestamp() - $from->getTimestamp();
        
        if ($secondsDiff < 60) {
            return $this->translator->trans($translationPrefix . 'lessThenAMinuteAgo', [], $translationDomain);
        }
        
        if ($secondsDiff < 3600) {
            $minutes = (int)round($secondsDiff / 60, 0);
            return $this->translator->transChoice($translationPrefix . 'minutesAgo', $minutes, ['%count%' => $minutes], $translationDomain);
        }
        
        if ($secondsDiff < 86400) {
            $hours = (int)round($secondsDiff / 3600, 0);
            return $this->translator->transChoice($translationPrefix . 'hoursAgo', $hours, ['%count%' => $hours], $translationDomain);
        }
        
        return $this->translator->trans($translationPrefix . 'exactDate', ['%date%'=>$from->format($defaultDateFormat)], $translationDomain);
    }
    
    /**
     * 
     * @param array $options keys: clickedPanel, allOpenHref, allClosedHref, firstPanelHref, secondPanelHref
     * @return array [ first => [open=>boolean, href=>], second => [open=>boolean, href=>] ]
     */
    public function renderAccordionLinks(array $options)
    {
        $clickedPanel = $options['clickedPanel'];
        $bothOpenHref = $options['bothOpenHref'];
        $allClosedHref = $options['allClosedHref'];
        $firstPanelHref = $options['firstPanelHref'];
        $secondPanelHref = $options['secondPanelHref'];
        $onlyOneATime = $options['onlyOneATime'];
        
        // default: closed
        $ret = [
            'first' => [
                'open' => false,
                'href' => $firstPanelHref,
            ],
            'second' => [
                'open' => false,
                'href' => $secondPanelHref,
            ]
        ];
        
        switch ($clickedPanel) {
            case $firstPanelHref:
                $ret['first']['open'] = true;
                $ret['first']['href'] = $allClosedHref;
                $ret['second']['href'] = $onlyOneATime ? $secondPanelHref : $bothOpenHref;
                break;
            
            case $secondPanelHref:
                $ret['second']['open'] = true;
                $ret['first']['href'] = $onlyOneATime ? $firstPanelHref : $bothOpenHref;
                $ret['second']['href'] = $allClosedHref;
                break;
            
            case $bothOpenHref:
                $ret['first']['open'] = true;
                $ret['second']['open'] = true;
                $ret['first']['href'] = $secondPanelHref;
                $ret['second']['href'] = $firstPanelHref;
                break;
        }
        
        return $ret;
    }
    
    /**
     * 
     * @param string $barName
     * @param integer $activeStepNumber
     */
    public function progressBar($barName, $activeStepNumber)
    {
        if (empty($this->params['progress_bars'][$barName])) {
            return "[ Progress bar $barName not found or empty, check your configuration files ]";
        }
        
        $steps = [];
        // set classes and labels from translation
        foreach ($this->params['progress_bars'][$barName] as $stepNumber) {
            $steps[] = [
                'label' => $this->translator->trans($barName . '.' . $stepNumber . '.label', [], 'progress-bar'),
                'class' => (($stepNumber == $activeStepNumber)     ? ' progress--active '    : '')
                         . (($stepNumber < $activeStepNumber)      ? ' progress--completed ' : '')
                         . (($stepNumber == $activeStepNumber - 1) ? ' progress--previous '  : '')
            ];
        }
        
        echo $this->environment->render('AppBundle:Components/Navigation:_progress-indicator.html.twig', [
            'progressSteps' => $steps
        ]);
    }
    
    public function getName()
    {
        return 'components_extension';
    }
}