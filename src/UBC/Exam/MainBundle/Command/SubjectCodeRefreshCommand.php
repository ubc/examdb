<?php


namespace UBC\Exam\MainBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;
use UBC\LtCommons\Provider\XMLDataProvider;
use UBC\LtCommons\Serializer\JMSSerializer;

class SubjectCodeRefreshCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('exam:subjectcode:refresh')
            ->setDescription('Refresh subject code')
            ->addOption(
                'local',
                null,
                InputOption::VALUE_NONE,
                'Load the local SQL instead fetching from SIS API'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('local')) {
            list($subjectCodes, $departmentCodes) = $this->refreshFromLocal($output);
        } else {
            list($subjectCodes, $departmentCodes) = $this->refreshFromSIS($output);
        }

        $output->writeln('Merging codes...');

        // convert department code array to an associate array for searching by code
        $departmentCodeArray = array();
        foreach ($departmentCodes->codes as $code) {
            $departmentCodeArray[$code->getCode()] = $code;
        }

        $entities = array();
        foreach ($subjectCodes->codes as $code) {
            $scode = new SubjectFaculty();
            $scode->setUrn($code->getId());
            //$scode->setCode($code->getCode());
            // temp fix as sis api missing code value for subject_code API
            $c = explode(':', $code->getId());
            $c = explode('~', $c[3]);
            $scode->setCode($c[0]);
            $scode->setCampus($code->getAdminCampusCode());
            $scode->setDepartment($code->getDepartmentCode());
            $scode->setTitle($code->getFullDescription());
            $deptCode = $code->getDepartmentCode();
            if (!empty($deptCode) && array_key_exists($deptCode, $departmentCodeArray)) {
                $departmentCode = $departmentCodeArray[$deptCode];
                $scode->setFaculty($departmentCode->getAdminFacultyCode());
            }
            $entities[] = $scode;
        }

        $output->writeln('Persisting into database...');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getRepository('UBCExamMainBundle:SubjectFaculty')->refresh($entities);

        $output->writeln('Done! Loaded ' . count($entities) . ' subject codes.');
    }

    protected function refreshFromLocal(OutputInterface $logger)
    {
        $path = __DIR__ . '/../Resources/fixtures/';
        $provider = new XMLDataProvider($path, new JMSSerializer());

        $logger->writeln('Loading subject code from XML in ' . $path);
        $subjectCodes = $provider->getSubjectCodes();

        $logger->writeln('Loading department code from XML in ' . $path);
        $departmentCodes = $provider->getDepartmentCodes();

        return array($subjectCodes, $departmentCodes);
    }

    /**
     * @param OutputInterface $logger
     * @return array
     */
    protected function refreshFromSIS(OutputInterface $logger)
    {
        $logger->writeln('Calling SIS API to get subject codes...');

        $subjectCodeService = $this->getContainer()->get('ubc_lt_commons.service.subject_code');
        $subjectCodes = $subjectCodeService->getSubjectCodes();

        $logger->writeln('Calling SIS API to get faculty codes...');

        $departmentCodeService = $this->getContainer()->get('ubc_lt_commons.service.department_code');
        $departmentCodes = $departmentCodeService->getDepartmentCodes();

        return array($subjectCodes, $departmentCodes);
    }
}