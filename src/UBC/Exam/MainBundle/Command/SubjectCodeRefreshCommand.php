<?php


namespace UBC\Exam\MainBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;

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
        $serializer = $this->getContainer()->get('sisapi.serializer');

        $locator = new FileLocator(array(__DIR__ . '/../Resources/fixtures'));
        $subjectCodes = array();
        $departmentCodes = array();

        $xmlFiles = $locator->locate('subject_code.xml', null, false);
        if (count($xmlFiles) == 1) {
            $logger->writeln('Loading XM from ' . $xmlFiles[0]);
            $subjectCodes =
                $serializer->deserialize(
                    file_get_contents($xmlFiles[0]),
                    'UBC\SISAPI\Entity\SubjectCodes',
                    'xml'
                );
        }

        $xmlFiles = $locator->locate('department_code.xml', null, false);
        if (count($xmlFiles) == 1) {
            $logger->writeln('Loading XML from ' . $xmlFiles[0]);
            $departmentCodes =
                $serializer->deserialize(
                    file_get_contents($xmlFiles[0]),
                    'UBC\SISAPI\Entity\DepartmentCodes',
                    'xml'
                );
        }

        return array($subjectCodes, $departmentCodes);
    }

    /**
     * @param OutputInterface $logger
     * @return array
     */
    protected
    function refreshFromSIS(OutputInterface $logger)
    {
        $logger->writeln('Calling SIS API to get subject codes...');

        $subjectCodeService = $this->getContainer()->get('sisapi.subject_code');
        $subjectCodes = $subjectCodeService->getSubjectCodes();

        $logger->writeln('Calling SIS API to get faculty codes...');

        $departmentCodeService = $this->getContainer()->get('sisapi.department_code');
        $departmentCodes = $departmentCodeService->getDepartmentCodes();

        return array($subjectCodes, $departmentCodes);
    }
}