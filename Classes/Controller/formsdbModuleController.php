<?php
/**
 * This file is part of the "forms2db" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace WACON\Forms2db\Controller;
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

final class FormsdbModuleController extends ActionController
{
   
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly MailRepository $mailRepository,
        protected readonly PageRepository $pageRepository,
        private readonly ConnectionPool $connectionPool,
    ) {
    }
    /**
     * Form Overview
     *
     * @throws InvalidQueryException|DBALException
     * @internal
     * @noinspection PhpUndefinedMethodInspection
     */
    public function listAction(int $page = 1): ResponseInterface
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
                $page = $this->pageRepository->getPage($row['pid']);
                $myrow['page_id']= $row['pid'];
                $myrow['page_title']= $page['title'];
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
     * @todo Add more charsets?
     */
    public function showAction(): ResponseInterface
    {
        $charset = 'UTF-8';
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($this->request->getArguments());
        if(array_key_exists('plugin', $this->request->getArguments())){
        $plugin = $this->request->getArgument('plugin');
        $mails = $this->mailRepository->findByPlugin($this->request->getArgument('plugin'));
        $formIdentifier = 'page-'.$plugin['page_id'].'_plugin-'.$plugin['plugin_id'].'_form-'.$plugin['form_id'].'_'.date("Y-m-d");
        
        //$formPersistenceIdentifier = $this->request->getArgument('formPersistenceIdentifier');
        //$filtered = $this->request->hasArgument('filtered') === true && $this->request->getArgument('filtered') === '1';
       
        $csvContent = '';
        $i=0;
        foreach ($mails as $result) {
            $jsonDecoded = json_decode($result->getMail(), true);
           
            
            
            if (is_array($jsonDecoded)) {
                if($i==0){
                    
                    $i++;
                    foreach ($jsonDecoded as $key => $value)
                    {
                        $csvContent.= $key.';';
                    }
                    $csvContent .= '
';
                    
                }
                $csvContent .=  implode(';', $jsonDecoded).'
';
              }
        }
    }
        //$csvContent = "\xEF\xBB\xBF" . $this->getCsvContent($formPersistenceIdentifier, $filtered);
       // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($csvContent);
        return $this->responseFactory
            ->createResponse()
            ->withHeader(
                'Content-Type',
                sprintf('application/json; charset=%s', $charset ?? 'utf-8')
            )
            ->withHeader(
                'Content-Disposition',
                sprintf('attachment; filename="%s";', $formIdentifier.'.csv')
            )
            ->withHeader(
                'Content-Length',
                (string)strlen($csvContent)
            )
            ->withBody($this->streamFactory->createStream((string)($csvContent)));

    }
   

    /**
     * Register document header buttons
     *
     * @param string|null $formPersistenceIdentifier
     * @param bool $showCsvDownload
     * @noinspection PhpUndefinedMethodInspection
     */
    protected function registerDocheaderButtons(
        string $formPersistenceIdentifier = null,
        bool $showCsvDownload = false
    ): void {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();
/*
        if ($this->request->getControllerActionName() === 'show') {
            $backFormButton = $buttonBar->makeLinkButton()
                ->setHref($this->getModuleUrl('web_FormToDatabaseFormresults'))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:show.buttons.backlink'))
                ->setShowLabelText(true)
                //->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL))
                ;
            $buttonBar->addButton($backFormButton, ButtonBar::BUTTON_POSITION_LEFT);

            if ($formPersistenceIdentifier !== null && $showCsvDownload === true) {
                $urlParameters = [
                    'formPersistenceIdentifier' => $formPersistenceIdentifier,
                ];

                // Full list download-button
                $downloadCsvFormButton = $buttonBar->makeLinkButton()
                    ->setHref($this->uriBuilder->uriFor('downloadCsv', $urlParameters))
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:show.buttons.download_csv'))
                    ->setShowLabelText(true)
                    //->setIcon($this->iconFactory->getIcon('actions-download',Icon::SIZE_SMALL))
                    ;
                $buttonBar->addButton($downloadCsvFormButton, ButtonBar::BUTTON_POSITION_LEFT, 2);

                // Filtered list download-button
                $urlParameters['filtered'] = true;
                $downloadCsvFormButton = $buttonBar->makeLinkButton()
                    ->setHref($this->uriBuilder->uriFor('downloadCsv', $urlParameters))
                    ->setTitle($this->getLanguageService()->sL('LLL:EXT:form_to_database/Resources/Private/Language/locallang_be.xlf:show.buttons.download_csv_filtered'))
                    ->setShowLabelText(true)
                    //->setIcon($this->iconFactory->getIcon('actions-download', Icon::SIZE_SMALL))
                    ;
                $buttonBar->addButton($downloadCsvFormButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            }
        }

        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref(GeneralUtility::getIndpEnv('REQUEST_URI'))
            ->setTitle('reload')
            //->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL))
            ;
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
*/
       
    }
}