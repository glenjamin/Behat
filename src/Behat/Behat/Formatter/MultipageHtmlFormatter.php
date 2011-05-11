<?php

namespace Behat\Behat\Formatter;

use Behat\Behat\Definition\Definition,
    Behat\Behat\DataCollector\LoggerDataCollector,
    Symfony\Component\Console\Output\StreamOutput;

use Behat\Behat\Event\FeatureEvent,
    Behat\Behat\Event\ScenarioEvent,
    Behat\Behat\Event\OutlineExampleEvent;

use Behat\Gherkin\Node\AbstractNode,
    Behat\Gherkin\Node\FeatureNode,
    Behat\Gherkin\Node\BackgroundNode,
    Behat\Gherkin\Node\AbstractScenarioNode,
    Behat\Gherkin\Node\OutlineNode,
    Behat\Gherkin\Node\ScenarioNode,
    Behat\Gherkin\Node\StepNode,
    Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    Behat\Behat\Exception\FormatterException,
    Behat\Behat\Console\Formatter\OutputFormatter;

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Multipage HTML formatter.
 *
 * @author      Konstantin Kudryashov <ever.zet@gmail.com>
 */
class MultipageHtmlFormatter extends HtmlFormatter
{
    const INDEX_FILENAME = 'index.html';
    const FAILURES_FILENAME = 'failures.html';
    const FEATURES_DIR = 'features';

    /**
     * Index output console.
     *
     * @var     Symfony\Component\Console\Output\StreamOutput
     */
    private $index;

    /**
     * Failures output console.
     *
     * @var     Symfony\Component\Console\Output\StreamOutput
     */
    private $failures;

    /**
     * Current HTML filename.
     *
     * @var     string
     */
    protected $filename;

    /**
     * Deffered header template part.
     *
     * @var     string
     */
    protected $header;

    /**
     * Per-feature scenarios counter
     *
     * @var     integer
     */
    protected $featureScenarios;

    /**
     * Per-feature result tracking
     *
     * @var string
     */
    protected $featureResult;

    /**
     * {@inheritdoc}
     */
    public function beforeFeature(FeatureEvent $event)
    {
        $feature = $event->getFeature();
        $this->filename = $this->featureOutputFilename($feature);

        $this->featureScenarios = 0;
        $this->featureResult = 'passed';
        $this->isBackgroundPrinted = false;
        $this->printFeatureHeader($feature);
    }

    /**
     * {@inheritdoc}
     */
    public function afterFeature(FeatureEvent $event)
    {
        $this->printIndexFeatureSummary($event->getFeature());
        $this->printFeatureFooter($event->getFeature());
        $this->flushOutputConsole();
    }

    /**
     * {@inheritdoc}
     */
    public function afterScenario(ScenarioEvent $event)
    {
        $this->featureScenarios += 1;
        $result = $event->getResult();
        if ($result > $this->featureResult)
            $this->featureResult = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function afterOutlineExample(OutlineExampleEvent $event)
    {
        $this->featureScenarios += 1;
        $result = $event->getResult();
        if ($result > $this->featureResult)
            $this->featureResult = $result;
    }

    /**
     * {@inheritdoc}
     */
    public function printSuiteHeader(LoggerDataCollector $logger)
    {
        $template = $this->getHtmlTemplate();
        $this->header = mb_substr($template, 0, mb_strpos($template, '{{content}}'));
        $this->footer = mb_substr($template, mb_strpos($template, '{{content}}') + 11);

        $this->indexWriteln($this->header);
        $this->indexWriteln('<h1>Behat Test Suite</h1>');
        $this->indexWriteln('<table class="index"><thead><tr>');
        $this->indexWriteln('<th>'.'Feature'.'</th>');
        $this->indexWriteln('<th>'.'Scenarios'.'</th>');
        $this->indexWriteln('<th>'.'Result'.'</th>');
        $this->indexWriteln('</tr></thead><tbody>');
    }

    /**
     * {@inheritdoc}
     */
    protected function printSuiteFooter(LoggerDataCollector $logger)
    {
        $this->indexWriteln('</tbody></table>');
        $this->console = $this->index;
        $this->printSummary($logger);
        $this->indexWriteln($this->footer);
    }

    /**
     * {@inheritdoc}
     */
    protected function printFeatureHeader(FeatureNode $feature)
    {
        $this->writeln($this->header);
        $this->writeln('<div class="back"><a href="javascript:history.go(-1)">Back to index</a></div>');
        $this->writeln('<div class="feature">');

        parent::printFeatureHeader($feature);
    }

    /**
     * {@inheritdoc}
     */
    protected function printFeatureFooter(FeatureNode $feature)
    {
        $this->writeln('</div>');
        $this->writeln($this->footer);
    }

    protected function printIndexFeatureSummary(FeatureNode $feature)
    {
        $href = $this->featureOutputFilename($feature);
        $title = $feature->getTitle();
        $this->indexWriteln('<tr class="'.$this->featureResult.'">');
        $this->indexWriteln('<td><a href="'.$href.'">'.$title.'</a></td>');
        $this->indexWriteln('<td>'.$this->featureScenarios.'</td>');
        $this->indexWriteln('<td>'.$this->featureResult.'</td>');
        $this->indexWriteln('</tr>');
    }

    /**
     * Get the output filename to be used for a feature file
     */
    protected function featureOutputFilename(FeatureNode $feature) {
        $relative = $this->relativizePathsInString($feature->getFile());
        return self::FEATURES_DIR . DIRECTORY_SEPARATOR . $relative . '.html';
    }

    /**
     * Writes message(s) to index.
     *
     * @param   string|array    $messages   message or array of messages
     * @param   boolean         $newline    do we need to append newline after messages
     *
     * @uses    getWritingConsole()
     */
    protected function indexWrite($messages, $newline = false)
    {
        $this->getIndexConsole()->write($messages, $newline);
    }

    /**
     * Writes newlined message(s) to index.
     *
     * @param   string|array    $messages   message or array of messages
     */
    protected function indexWriteln($messages = '')
    {
        $this->indexWrite($messages, true);
    }

    /**
     * Returns index console instance, prepared to write.
     *
     * @return  Symfony\Component\Console\Output\StreamOutput
     *
     * @uses    createOutputConsole()
     * @uses    configureOutputConsole()
     */
    protected function getIndexConsole()
    {
        if (null === $this->index) {
            $this->index = $this->createOutputConsole(self::INDEX_FILENAME);
        }
        $this->configureOutputConsole($this->index);

        return $this->index;
    }

    /**
     * Writes message(s) to index.
     *
     * @param   string|array    $messages   message or array of messages
     * @param   boolean         $newline    do we need to append newline after messages
     *
     * @uses    getWritingConsole()
     */
    protected function failuresWrite($messages, $newline = false)
    {
        $this->getFailuresConsole()->write($messages, $newline);
    }

    /**
     * Writes newlined message(s) to index.
     *
     * @param   string|array    $messages   message or array of messages
     */
    protected function failuresWriteln($messages = '')
    {
        $this->failuresWrite($messages, true);
    }

    /**
     * Returns index console instance, prepared to write.
     *
     * @return  Symfony\Component\Console\Output\StreamOutput
     *
     * @uses    createOutputConsole()
     * @uses    configureOutputConsole()
     */
    protected function getFailuresConsole()
    {
        if (null === $this->failures) {
            $this->failures = $this->createOutputConsole(self::FAILURES_FILENAME);
        }
        $this->configureOutputConsole($this->failures);

        return $this->failures;
    }

    /**
     * {@inheritdoc}
     */
    protected function createOutputStream($filename = null)
    {
        $outputPath = $this->parameters->get('output_path');

        if (null === $outputPath) {
            throw new FormatterException(sprintf(
                'You should specify "output_path" parameter for %s', get_class($this)
            ));
        } elseif (is_file($outputPath)) {
            throw new FormatterException(sprintf(
                'Directory path expected as "output_path" parameter of %s, but got: %s',
                get_class($this),
                $outputPath
            ));
        }

        if (!$filename)
            $filename = $this->filename;

        $file = $outputPath . DIRECTORY_SEPARATOR . $filename;

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        return fopen($file, 'w');
    }

    /**
     * Returns new output console.
     *
     * @return  Symfony\Component\Console\Output\StreamOutput
     *
     * @uses    createOutputStream()
     */
    protected function createOutputConsole($filename = null)
    {
        $stream = $this->createOutputStream($filename);

        return new StreamOutput($stream, StreamOutput::VERBOSITY_NORMAL, null, new OutputFormatter());
    }

    /**
     * Get HTML template.
     *
     * @return  string
     */
    protected function getHtmlTemplate()
    {
        $templatePath = $this->parameters->get('template_path')
                     ?: $this->parameters->get('support_path') . DIRECTORY_SEPARATOR . 'html.tpl';

        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }

        return <<<HTMLTPL
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns ="http://www.w3.org/1999/xhtml">
<head>
    <meta content="text/html;charset=utf-8"/>
    <title>Behat Test Suite</title>
    <link href="http://fonts.googleapis.com/css?family=Lobster" rel="stylesheet" type="text/css"/>
    <style type="text/css">
        body {
            margin:0px;
            padding:15px;
            position:relative;
        }
        #behat {
            font-family: Georgia, serif;
            font-size:18px;
            line-height:26px;
        }
        #behat h2, #behat h3, #behat h4 {
            margin:0px 0px 5px 0px;
            padding:0px;
            font-family:Georgia;
        }
        #behat h2 .title, #behat h3 .title, #behat h4 .title {
            font-weight:normal;
        }
        #behat .index {
            border-collapse: collapse;
            width: 100%;
        }
        #behat .index th, #behat .index td {
            text-align: left;
            border: 2px solid #666;
            padding: 5px;
        }
        #behat .path {
            font-size:10px;
            font-weight:normal;
            font-family: 'Bitstream Vera Sans Mono', 'DejaVu Sans Mono', Monaco, Courier, monospace !important;
            color:#999;
            padding:0px 5px;
            float:right;
        }
        #behat h3 .path {
            margin-right:4%;
        }
        #behat ul.tags {
            font-size:14px;
            font-weight:bold;
            color:#246AC1;
            list-style:none;
            margin:0px;
            padding:0px;
        }
        #behat ul.tags li {
            display:inline;
        }
        #behat ul.tags li:after {
            content:' ';
        }
        #behat ul.tags li:last-child:after {
            content:'';
        }
        #behat .feature > p {
            margin-top:0px;
            margin-left:20px;
        }
        #behat .scenario {
            margin-left:20px;
            margin-bottom:40px;
        }
        #behat .scenario > ol {
            margin:0px;
            list-style:none;
            margin-left:20px;
            padding:0px;
        }
        #behat .scenario > ol:after {
            content:'';
            display:block;
            clear:both;
        }
        #behat .scenario > ol li {
            float:left;
            width:95%;
            padding-left:5px;
            border-left:5px solid;
            margin-bottom:4px;
        }
        #behat .scenario > ol li .argument {
            margin:10px 20px;
            font-size:16px;
        }
        #behat .scenario > ol li table.argument {
            border:1px solid #d2d2d2;
        }
        #behat .scenario > ol li table.argument thead td {
            font-weight: bold;
        }
        #behat .scenario > ol li table.argument td {
            padding:5px 10px;
            background:#f3f3f3;
        }
        #behat .scenario > ol li .keyword {
            font-weight:bold;
        }
        #behat .scenario > ol li .path {
            float:right;
        }
        #behat .scenario .examples {
            margin-top:20px;
            margin-left:40px;
        }
        #behat .scenario .examples table {
            margin-left:20px;
        }
        #behat .scenario .examples table thead td {
            font-weight:bold;
            text-align:center;
        }
        #behat .scenario .examples table td {
            padding:2px 10px;
            font-size:16px;
        }
        #behat .scenario .examples table .failed.exception td {
            border-left:5px solid #000;
            border-color:#C20000 !important;
            padding-left:0px;
        }
        pre {
            font-family:monospace;
        }
        .snippet {
            font-size:14px;
            color:#000;
            margin-left:20px;
        }
        .backtrace {
            font-size:12px;
            color:#C20000;
            overflow:hidden;
            margin-left:20px;
        }
        #behat .passed {
            background:#DBFFB4;
            border-color:#65C400 !important;
            color:#3D7700;
        }
        #behat .failed {
            background:#FFFBD3;
            border-color:#C20000 !important;
            color:#C20000;
        }
        #behat .undefined, #behat .pending {
            border-color:#FAF834 !important;
            background:#FCFB98;
            color:#000;
        }
        #behat .skipped {
            background:lightCyan;
            border-color:cyan !important;
            color:#000;
        }
        #behat .summary {
            margin: 15px;
            padding: 10px;
            border-width: 2px;
            border-style: solid;
        }
    </style>
</head>
<body>
    <div id="behat">
        {{content}}
    </div>
</body>
</html>
HTMLTPL;
    }
}