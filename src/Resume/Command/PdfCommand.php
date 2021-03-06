<?php
namespace Resume\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PdfCommand extends HtmlCommand
{
    protected function configure()
    {
        $this
            ->setName('pdf')
            ->setDescription('Generate a PDF from a markdown file')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Source markdown document'
            )
            ->addArgument(
                'destination',
                InputArgument::REQUIRED,
                'Output destination folder'
            )
            ->addOption(
                'template',
                't',
                InputOption::VALUE_OPTIONAL,
                'Which of the templates to use'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app    = $this->getApplication();
        $source       = $input->getArgument('source');
        $destination  = trim($input->getArgument('destination'), DIRECTORY_SEPARATOR);
        $template     = $input->getOption('template');
        $pdfSource    = join(DIRECTORY_SEPARATOR, array($destination, '.tmp_pdf_source.html'));
        $destFilename = join(DIRECTORY_SEPARATOR, array($destination, pathinfo($source, PATHINFO_FILENAME) . '.pdf'));

        // Make sure we've got out converter available
        exec('wkhtmltopdf -V', $results, $returnVal);
        if ($returnVal) {
            $output->writeln(
                "\n<error>Error:</error> Unable to locate wkhtmltopdf.\n" .
                "  Please make sure that it is installed and available in " .
                "your path. \n  For installation help, please read: " .
                "https://github.com/pdfkit/pdfkit/wiki/Installing-WKHTMLTOPDF \n\n",
                $this->app->outputFormat
            );

            return false;
        }

        $rendered = $this->generateHtml($source, $template, false);

        // The pdf needs some extra css rules, and so we'll add them here
        // to our html document
        $simpleDom = new \simple_html_dom($rendered);
        $body = $simpleDom->find('body', 0);
        $body->class = $body->class . ' pdf';
        $rendered = (string) $simpleDom;

        // Save to a temp destination for the pdf renderer to use
        file_put_contents($pdfSource, $rendered);

        // Process the document with wkhtmltopdf
        exec('wkhtmltopdf ' . $pdfSource .' ' . $destFilename);

        // Unlink the temporary file
        unlink($pdfSource);

        $output->writeln(
            sprintf(
                "Wrote pdf resume to: <info>%s</info>",
                $destFilename
            ),
            $this->app->outputFormat
        );

        return true;
    }
}

/* End of file PdfCommand.php */
