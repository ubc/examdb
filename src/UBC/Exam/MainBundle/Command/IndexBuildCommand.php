<?php


namespace UBC\Exam\MainBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndexBuildCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('exam:index:build')
            ->setDescription('Build the exam indexes')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $engine = $this->getContainer()->get('ivory_lucene_search');
        $output->writeln('Removing old indexes...');

        $engine->eraseIndex('exams');

        $output->writeln('Generating new indexes...');

        $indexer = $engine->getIndex('exams');

        $repo = $this->getContainer()->get('doctrine')->getRepository('UBCExamMainBundle:Exam');
        $exams = $repo->findAll();

        foreach ($exams as $exam) {
            $exam->index($indexer, false, false);
        }

        $indexer->commit();

        $output->writeln('Optimizing indexes...');

        $indexer->optimize();

        $output->writeln('Finished. There are ' . $indexer->numDocs() . ' exams indexed.');
    }


}