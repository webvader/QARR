<?php
/**
 * QARR plugin for Craft CMS 3.x
 *
 * Questions & Answers and Reviews & Ratings
 *
 * @link      https://owl-design.net
 * @copyright Copyright (c) 2018 Vadim Goncharov
 */

namespace owldesign\qarr\controllers;

use Craft;
use craft\web\View;
use craft\web\Controller;
use craft\helpers\Template;
use craft\controllers\ElementIndexesController;
use yii\web\Response;

use owldesign\qarr\QARR;

/**
 * Class ElementsController
 * @package owldesign\qarr\controllers
 */
class ElementsController extends Controller
{
    // Protected Properties
    // =========================================================================

    /**
     * @var array
     */
    protected $allowAnonymous = true;

    // Public Methods
    // =========================================================================

    public function actionQueryElements()
    {
        $this->requirePostRequest();

        $request    = Craft::$app->getRequest();
        $type       = $request->getBodyParam('type');
        $limit      = $request->getBodyParam('limit');
        $offset     = $request->getBodyParam('offset');
        $productId  = $request->getBodyParam('productId');

        $variables['entries'] = QARR::$plugin->elements->queryElements($type, $productId, $limit, $offset);

        $oldPath = Craft::$app->view->getTemplateMode();
        Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $template = Craft::$app->view->renderTemplate('qarr/frontend/'. $type .'/_entries', $variables);

        Craft::$app->view->setTemplateMode($oldPath);

        return $this->asJson([
            'success' => true,
            'template'   => Template::raw($template)
        ]);

    }

    public function actionCheckPending()
    {
        $this->requirePostRequest();

        $reviews = QARR::$plugin->elements->getCount('reviews', 'pending');
        $questions = QARR::$plugin->elements->getCount('questions', 'pending');

        $total = $reviews + $questions;

        return $this->asJson([
            'success' => true,
            'reviews' => $reviews,
            'questions' => $questions,
            'total'   => $total,
        ]);
    }

    /**
     * @return bool|Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionReportAbuse()
    {
        $this->requirePostRequest();

        $request    = Craft::$app->getRequest();
        $elementId  = $request->getBodyParam('id');
        $type       = $request->getBodyParam('type');

        if (!$elementId && !$type) {
            return false;
        }

        $result = QARR::$plugin->elements->reportAbuse($elementId, $type);
        $entry  = QARR::$plugin->reviews->getEntryById($elementId);

        if ($result) {
            // TODO: Setup up sometype of front end cookies
            // QARR::$plugin->cookies->set('reported', $elementId);

            return $this->asJson([
                'success' => true,
                'entry' => $entry
            ]);
        } else {
            return $this->asJson([
                'success' => false
            ]);
        }
    }

    /**
     * @return bool|Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionClearAbuse()
    {
        $this->requirePostRequest();

        $request    = Craft::$app->getRequest();
        $elementId  = $request->getBodyParam('id');
        $type       = $request->getBodyParam('type');

        if (!$elementId && !$type) {
            return false;
        }

        $result = QARR::$plugin->elements->clearAbuse($elementId, $type);

        if ($result) {
            return $this->asJson([
                'success' => true
            ]);
        } else {
            return $this->asJson([
                'success' => false
            ]);
        }
    }

    /**
     * @return null|Response
     * @throws \yii\web\BadRequestHttpException
     */
    public function actionUpdateStatus()
    {
        $this->requirePostRequest();

        $request    = Craft::$app->getRequest();
        $elementId  = $request->getBodyParam('id');
        $status     = $request->getBodyParam('status');
        $type       = $request->getBodyParam('type');

        if (!$elementId && !$type) {
            return null;
        }

        $result = QARR::$plugin->elements->updateStatus($elementId, $status, $type);
        $entry  = QARR::$plugin->elements->getElement($type, $elementId);

        if (!$result) {
            return null;
        }

        return $this->asJson([
            'success' => true,
            'entry' => $entry
        ]);
    }
}