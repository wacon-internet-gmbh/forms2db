<?php
declare(strict_types=1);
/**
 * This file is part of the "forms2db" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace WACON\Forms2db\Controller\Backend;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use WACON\Forms2db\Domain\Repository\MailRepository;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;

#[AsController]
final class FormsdbModuleController extends ActionController
{
    private ExtensionConfiguration $extensionConfiguration;
    private FormPersistenceManager $formPersistenceManager;
    private ArrayFormFactory $arrayFormFactory;

    /**
     * @param ModuleTemplateFactory $moduleTemplateFactory
     * @param MailRepository $mailRepository
     * @param PageRepository $pageRepository
     * @param ConnectionPool $connectionPool
     */
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly MailRepository $mailRepository,
        protected readonly PageRepository $pageRepository,
        private readonly ConnectionPool $connectionPool,
    ) {
    }

    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function injectFormPersistenceManager(FormPersistenceManager $formPersistenceManager): void
    {
        $this->formPersistenceManager = $formPersistenceManager;
    }

    public function injectArrayFormFactory(ArrayFormFactory $arrayFormFactory): void
    {
        $this->arrayFormFactory = $arrayFormFactory;
    }
    
    /**
     * Form Overview
     *   * @return ResponseInterface
     *
     */
    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_forms2db_domain_model_mail');
        $result = $queryBuilder->select('pid','plugin_id', 'form_id')
            ->from('tx_forms2db_domain_model_mail')
            ->groupBy('pid','plugin_id','form_id')
            ->executeQuery();
        $plugins = array();
        while ($row = $result->fetchAssociative()) {
            // Do something with that single row
            $myrow = array();

            $page = $this->pageRepository->getPage($row['pid'],true);
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_forms2db_domain_model_mail');
            $myrow['count'] = $queryBuilder
                ->count('uid')
                ->from('tx_forms2db_domain_model_mail')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($row['pid'], Connection::PARAM_STR)),
                    $queryBuilder->expr()->eq('plugin_id', $queryBuilder->createNamedParameter($row['plugin_id'], Connection::PARAM_STR)),
                    $queryBuilder->expr()->eq('form_id', $queryBuilder->createNamedParameter($row['form_id'], Connection::PARAM_STR))
                )
                ->executeQuery()
                ->fetchOne();
        
            $myrow['page_id']= $row['pid'];
            if( $row['pid'])$myrow['page_title']= $page['title'];
            $myrow['plugin_id']= $row['plugin_id'];
            $myrow['form_id']= $row['form_id'];
            $plugins[] = $myrow;
        }
        $moduleTemplate->assign('plugins', $plugins);

        return $moduleTemplate->renderResponse('Formsdb/List');
    }



    /**
     * Downloads the current results list as CSV
     *
     * @throws NoSuchArgumentException
     * @throws Exception
     */
    public function deleteokAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);

        $moduleTemplate->assign('plugin', $this->request->getArgument('plugin'));
        return $moduleTemplate->renderResponse('Formsdb/Deleteok');

    }

    /**
     * Downloads the current results list as CSV
     *
     * @throws NoSuchArgumentException
     * @throws Exception
     */
    public function deleteAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $mails = $this->mailRepository->findByPlugin($this->request->getArgument('plugin'));
        foreach($mails as $mail){
            $this->mailRepository->remove ($mail);
        }
        $moduleTemplate->assign('plugin', $this->request->getArgument('plugin'));
        return $moduleTemplate->renderResponse('Formsdb/Delete');

    }

    /**
     * Downloads the current results list as CSV
     *
     * @throws NoSuchArgumentException
     * @throws Exception
     */
    public function showAction(): ResponseInterface
    {
        $charset = 'UTF-8';
        if(array_key_exists('plugin', $this->request->getArguments())){
            $plugin = $this->request->getArgument('plugin');
            $mails = $this->mailRepository->findByPlugin($this->request->getArgument('plugin'));
            $formIdentifier = 'page-'.$plugin['page_id'].'_plugin-'.$plugin['plugin_id'].'_form-'.$plugin['form_id'].'_'.date("Y-m-d");
            $formIdentifierlabel = 'Page-ID: '.$plugin['page_id'].', Plugin-ID: '.$plugin['plugin_id'].', Form-ID: '.$plugin['form_id'].', Date: '.date("Y-m-d");

            $useLabels = (bool)($this->extensionConfiguration->get('forms2db')['useLabelsForCsv'] ?? false);
            $labelMap = [];
            if ($useLabels && count($mails) > 0) {
                $firstMail = $mails->getFirst();
                if ($firstMail !== null) {
                    $persistenceIdentifier = $firstMail->getPersistenceId();
                    if ($persistenceIdentifier) {
                        try {
                            $formDefinitionArray = $this->formPersistenceManager->load($persistenceIdentifier);
                            $formDefinition = $this->arrayFormFactory->build($formDefinitionArray);
                            $renderables = $formDefinition->getRenderablesRecursively();
                            foreach ($renderables as $renderable) {
                                if ($renderable instanceof \TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface) {
                                    if($renderable->getLabel())$labelMap[$renderable->getIdentifier()] = $renderable->getLabel();
                                }
                            }
                        } catch (\Exception $e) {
                            // Fallback to identifiers if loading fails
                        }
                    }
                }
            }

            $csvContent = array();
            $i=0;
            foreach ($mails as $result) {
                $jsonDecoded = json_decode($result->getMail(), true);

                if (is_array($jsonDecoded)) {
                    if($i==0){
                        $csvRow = array();
                        $i++;
                        $csvRow[] = 'Date';
                        foreach ($jsonDecoded as $key => $value)
                        {
                            $label = ($useLabels && !empty($labelMap[$key])) ? $labelMap[$key] : $key;
                            $csvRow[] = $label;
                        }
                        $csvContent[] = $csvRow;

                    }
                    $csvRow = array();
                    $csvRow[] = date('d.m.Y, H:i',$result->getCrdate());
                    foreach ($jsonDecoded as $key => $value)
                    {
                        if(is_array($value)){
                            $csvRow[]= implode(',', $value);
                        }
                        else $csvRow[]= $value;
                    }
                    $csvContent[] = $csvRow;

                }
            }
        }
  
         $csvString = $this->generateCsv($csvContent);
        $stream = $this->streamFactory->createStream($csvString);
       $response = $this->responseFactory
            ->createResponse()
            ->withHeader(
                'Content-Type',
                sprintf('text/csv; charset=%s', $charset ?? 'utf-8')
            )
            ->withHeader(
                'Content-Disposition',
                sprintf('attachment; name="'.$formIdentifierlabel.'";filename="'.$formIdentifier.'.csv"')
            )
            ->withHeader(
                'Content-Length',
                sprintf('%d', $stream->getSize())
            )
           
            ->withBody($stream);
            
           return $response;

    }
protected function generateCsv($data) {
    $contents = '';
       $handle = fopen('php://temp', 'r+');
       foreach ($data as $line) {
               fputcsv($handle, $line);
       }
       rewind($handle);
       while (!feof($handle)) {
               $contents .= fread($handle, 8192);
       }
       fclose($handle);
       return $contents;
}

 /**
     * Downloads the current results list as json
     *
     * @throws NoSuchArgumentException
     * @throws Exception
     */
    public function jsonAction(): ResponseInterface
    {
        $charset = 'UTF-8';
        if(array_key_exists('plugin', $this->request->getArguments())){
            $plugin = $this->request->getArgument('plugin');
            $mails = $this->mailRepository->findByPlugin($this->request->getArgument('plugin'));
            $formIdentifier = 'page-'.$plugin['page_id'].'_plugin-'.$plugin['plugin_id'].'_form-'.$plugin['form_id'].'_'.date("Y-m-d");
            $formIdentifierlabel = 'Page-ID: '.$plugin['page_id'].', Plugin-ID: '.$plugin['plugin_id'].', Form-ID: '.$plugin['form_id'].', Date: '.date("Y-m-d");

            $useLabels = (bool)($this->extensionConfiguration->get('forms2db')['useLabelsForCsv'] ?? false);
            $labelMap = [];
            if ($useLabels && count($mails) > 0) {
                $firstMail = $mails->getFirst();
                if ($firstMail !== null) {
                    $persistenceIdentifier = $firstMail->getPersistenceId();
                    if ($persistenceIdentifier) {
                        try {
                            $formDefinitionArray = $this->formPersistenceManager->load($persistenceIdentifier);
                            $formDefinition = $this->arrayFormFactory->build($formDefinitionArray);
                            $renderables = $formDefinition->getRenderablesRecursively();
                            foreach ($renderables as $renderable) {
                                if ($renderable instanceof \TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface) {
                                    if($renderable->getLabel())$labelMap[$renderable->getIdentifier()] = $renderable->getLabel();
                                }
                            }
                        } catch (\Exception $e) {
                            // Fallback to identifiers if loading fails
                        }
                    }
                }
            }

            $csvContent = array();
            
            $j=0;

            foreach ($mails as $result) {
                $j++;

                    $csvContent[$j] = $result->getMail();

                
            }
        }

        return $this->responseFactory
            ->createResponse()
            ->withHeader(
                'Content-Type',
                sprintf('application/json; charset=%s', $charset ?? 'utf-8')
            )
            ->withHeader(
                'Content-Disposition',
                sprintf('attachment; name="'.$formIdentifierlabel.'";filename="'.$formIdentifier.'.json"')
            )
            ->withHeader(
                'Content-Length',
                (string)strlen(json_encode($csvContent))
            )
            ->withBody($this->streamFactory->createStream((string)(json_encode($csvContent))));
           

    }
    protected function convertToWindowsCharset($string) {
        $charset =  mb_detect_encoding(
            $string,
            "UTF-8, utf-8, ISO-8859-1, ISO-8859-15",
            true
        );

        $string =  mb_convert_encoding($string, "Windows-1252", $charset);
        return $string;
    }

    /**
     * Register document header buttons
     *
     * @param string|null $formPersistenceIdentifier
     * @param bool $showCsvDownload
     * @noinspection PhpUndefinedMethodInspection
     */
    protected function registerDocheaderButtons(
        ?string $formPersistenceIdentifier = null,
        bool $showCsvDownload = false
    ): void {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();


    }
}