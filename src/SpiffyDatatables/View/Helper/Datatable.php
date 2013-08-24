<?php

namespace SpiffyDatatables\View\Helper;

use SpiffyDatatables\Datatable as Table;
use SpiffyDatatables\DatatableManager;
use Zend\Json\Expr;
use Zend\Json\Json;
use Zend\View\Helper\AbstractHtmlElement;

class Datatable extends AbstractHtmlElement
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var DatatableManager
     */
    protected $manager;

    /**
     * @var array
     */
    protected $jsonExpressions = array();

    /**
     * @param DatatableManager $manager
     */
    public function __construct(DatatableManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param string|Table|null $nameOrDatatable
     * @param string $id
     * @param array $attributes
     * @return $this
     */
    public function __invoke($nameOrDatatable = null, $id = null, $attributes = array())
    {
        if ($nameOrDatatable) {
            $this->setId($id);
            $this->injectJs($nameOrDatatable);
            return $this->renderHtml($nameOrDatatable, $attributes);
        }
        return $this;
    }

    /**
     * Injects the Datatable javascript using the inlineScript helper.
     *
     * @param string|null $nameOrDatatable
     * @param string $placement prepend, append, or set
     */
    public function injectJs($nameOrDatatable, $placement = 'APPEND')
    {
        $js = sprintf('$(function() { %s });', $this->renderJavascript($nameOrDatatable));
        $this->getView()->inlineScript('script', $js, $placement);
    }

    /**
     * Renders the HTML for the Datatable.
     *
     * @param string|Table $nameOrDatatable
     * @param array $attributes
     * @return string
     */
    public function renderHtml($nameOrDatatable, array $attributes = array())
    {
        if (!$nameOrDatatable instanceof Table) {
            $nameOrDatatable = $this->manager->get($nameOrDatatable);
        }

        if (!isset($attributes['id'])) {
            $attributes['id'] = $this->extractId($nameOrDatatable);
        }

        $columns    = $nameOrDatatable->getColumns();
        $tableStart = sprintf('<table%s>%s', $this->htmlAttribs($attributes), PHP_EOL);
        $header     = '';

        if ($columns->count() > 0) {
            $header = str_repeat(' ', 4) . '<thead>' . PHP_EOL;
            $header.= str_repeat(' ', 8) . '<tr>' . PHP_EOL;

            /** @var $column \SpiffyDatatables\Column\AbstractColumn */
            foreach($nameOrDatatable->getColumns() as $column) {
                $title  = $column->getOption('sTitle') ? $column->getOption('sTitle') : '';
                $style  = ($column->getOption('bVisible') === false) ? ' style="display:none;"' : '';
                $header.= sprintf("%s<th%s>%s</th>%s", str_repeat(' ', 12), $style, $title, PHP_EOL);
            }

            $header  .= str_repeat(' ', 8) . '</tr>' . PHP_EOL;
            $header  .= str_repeat(' ', 4) . '</thead>' . PHP_EOL;
        }

        $body = str_repeat(' ', 4) . '<tbody>' . PHP_EOL;

        if ($nameOrDatatable->isServerSide()) {
            $body.= $this->getServerSideBody($nameOrDatatable);
        } else {
            $body.= $this->getStaticBody($nameOrDatatable);
        }

        $body.= str_repeat(' ', 4) . '</tbody>' . PHP_EOL;
        $tableEnd = '</table>';

        return $tableStart . $header . $body . $tableEnd;
    }

    /**
     * Renders the Javascript for the Datatable.
     *
     * @param string|Table $nameOrDatatable
     * @return string
     */
    public function renderJavascript($nameOrDatatable)
    {
        return sprintf(
            '$("#%s").dataTable(%s);',
            $this->extractId($nameOrDatatable),
            $this->renderOptionsJavascript($nameOrDatatable)
        );
    }

    /**
     * Renders only the options portion of the Javascript for Datatables. Useful for setting up
     * javascript instead of using the built in methods. If no custom options are passed in then the
     * options for the datatable are used.
     *
     * @param string|Table $nameOrDatatable
     * @param array|null $options
     * @return string
     */
    public function renderOptionsJavascript($nameOrDatatable, array $options = null)
    {
        if (!$nameOrDatatable instanceof Table) {
            $nameOrDatatable = $this->manager->get($nameOrDatatable);
        }

        $options              = $options ? $options : $nameOrDatatable->getOptions();
        $options['aoColumns'] = $nameOrDatatable->getColumns()->toArray();

        foreach($options as $key => $value) {
            if (in_array($key, $this->jsonExpressions)) {
                $input[$key] = new Expr($value);
            }
        }

        // datatables fails with [] instead of {} so cast to object to avoid that
        if (empty($options)) {
            $options = (object) $options;
        }

        $json = Json::encode($options, false, array('enableJsonExprFinder' => true));
        return Json::prettyPrint($json, array('indent' => "    "));
    }

    /**
     * @param string $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param Table $datatable
     * @return string
     */
    protected function getServerSideBody(Table $datatable)
    {
        $output = str_repeat(' ', 8) . '<tr>';
        $output.= sprintf(
            '<td colspan="%d">Loading data ...</td>',
            count($datatable->getColumns()->getColumns())
        );
        $output.= '</tr>' . PHP_EOL;

        return $output;
    }

    /**
     * @param string|Table $nameOrDatatable
     * @return string
     */
    protected function extractId($nameOrDatatable)
    {
        if ($this->id) {
            return $this->id;
        }

        if (is_string($nameOrDatatable)) {
            return $this->normalizeId($nameOrDatatable);
        }

        return 'datatable';
    }

    /**
     * @param Table $datatable
     * @throws \RuntimeException
     * @return string
     */
    protected function getStaticBody(Table $datatable)
    {
        $output = '';

        foreach($datatable->getDataResult()->getData() as $row) {
            $output.= str_repeat(' ', 8) . '<tr>' . PHP_EOL;

            /** @var $column \SpiffyDatatables\Column\AbstractColumn */
            foreach($datatable->getColumns() as $column) {
                $style  = ($column->getOption('bVisible') === false) ? ' style="display:none;"' : '';
                $value  = $column->getValue($row);

                $output.= sprintf("%s<td%s>%s</td>%s", str_repeat(' ', 12), $style, $value, PHP_EOL);
            }

            $output.= str_repeat(' ', 8) . '</tr>' . PHP_EOL;
        }

        return $output;
    }
}
