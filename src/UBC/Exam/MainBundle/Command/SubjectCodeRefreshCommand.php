<?php


namespace UBC\Exam\MainBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UBC\Exam\MainBundle\Entity\SubjectFaculty;

class SubjectCodeRefreshCommand extends ContainerAwareCommand {
    protected function configure()
    {
        $this
            ->setName('exam:subjectcode:refresh')
            ->setDescription('Refresh subject code')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');

        $logger->info('Calling SIS API to get subject codes...');

        $subjectCodeService = $this->getContainer()->get('sisapi.subject_code');
        $subjectCodes = $subjectCodeService->getSubjectCodes();

        $logger->info('Calling SIS API to get faculty codes...');

        $departmentCodeService = $this->getContainer()->get('sisapi.department_code');
        $departmentCodes = $departmentCodeService->getDepartmentCodes();

        $logger->info('Merging codes...');

        // convert department code array to an associate array for searching by code
        $departmentCodeArray = array();
        foreach($departmentCodes->codes as $code) {
            $departmentCodeArray[$code->getCode()] = $code;
        }

        $entities = array();
        foreach($subjectCodes->codes as $code) {
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

        $logger->info('Persisting into database...');

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getRepository('UBCExamMainBundle:SubjectFaculty')->refresh($entities);

        $logger->info('Done!');
    }
}