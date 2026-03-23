<?php

header('Content-Type: application/json');

$job_openings = [
    [
        'title' => 'Project Engineer',
        'location' => 'Hauppauge, NY',
        'job_type' => 'Full-time',
        'department' => 'Engineering',
        'description' => 'The Project Engineer will lead project execution and ensure adherence to project specifications and timelines.',
        'salary' => '$70,000 - $90,000',
        'requirements' => [
            'Bachelor’s degree in Engineering or related field',
            '5+ years of experience in construction project management',
            'Strong communication and leadership skills',
            'Proficiency in project management software'
        ],
        'contact' => [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '123-456-7890'
        ]
    ]
];

echo json_encode($job_openings);

?>