<?php
// Extended Eligibility Questionnaire Definitions & Server-Side Scoring Engine

/**
 * Returns complete definition of the 18+ questionnaire items grouped by section
 */
function get_questionnaire_definition(): array {
    return [
        'environment' => [
            'title' => 'Section A: Living Environment',
            'icon' => '🏡',
            'description' => 'Help us understand your home setup and space for this pet.',
            'questions' => [
                'q1_landlord_permission' => [
                    'number' => 1,
                    'text' => 'Do you have permission from your landlord or housing association to keep a pet, if applicable?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'yes_written' => ['label' => 'Yes, written permission or I own my home', 'points' => 5],
                        'verbal' => ['label' => 'Yes, verbal agreement from landlord/HOA', 'points' => 4],
                        'in_progress' => ['label' => 'Currently verifying / awaiting confirmation', 'points' => 2],
                        'not_yet' => ['label' => 'No or not yet checked', 'points' => 0],
                    ],
                ],
                'q2_housing_type' => [
                    'number' => 2,
                    'text' => 'What type of accommodation do you live in?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'house_yard' => ['label' => 'Independent house with fenced yard/garden', 'points' => 5],
                        'apartment_spacious' => ['label' => 'Apartment / Condominium with nearby parks', 'points' => 5],
                        'townhouse' => ['label' => 'Townhouse / Villa with patio', 'points' => 5],
                        'shared_room' => ['label' => 'Shared house / Rented room', 'points' => 3],
                    ],
                ],
                'q3_environment_suitable' => [
                    'number' => 3,
                    'text' => 'Is your home and surrounding environment suitable for this pet?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'fully_suitable' => ['label' => 'Extremely suitable, quiet, pet-friendly and secure', 'points' => 5],
                        'good_safe' => ['label' => 'Good and safe, minor pet-proofing underway', 'points' => 4],
                        'moderate' => ['label' => 'Moderate, need guidance on pet-proofing', 'points' => 3],
                        'unprepared' => ['label' => 'Not yet prepared', 'points' => 1],
                    ],
                ],
                'q4_safe_space' => [
                    'number' => 4,
                    'text' => 'Is there enough safe space for the pet to rest, move, and exercise?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'ample_space' => ['label' => 'Generous indoor and outdoor spaces', 'points' => 5],
                        'adequate_space' => ['label' => 'Adequate indoor space with accessible walking routes', 'points' => 5],
                        'moderate_space' => ['label' => 'Moderate space suitable for quiet animals', 'points' => 3],
                        'limited_space' => ['label' => 'Very limited space', 'points' => 1],
                    ],
                ],
                'q5_household_agreement' => [
                    'number' => 5,
                    'text' => 'Are all members of your household comfortable with adopting this pet?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'all_agree' => ['label' => 'Yes, everyone is enthusiastic and supportive', 'points' => 5],
                        'majority_agree' => ['label' => 'Yes, majority agrees and no one objects', 'points' => 4],
                        'some_hesitant' => ['label' => 'Some members are hesitant or need discussion', 'points' => 2],
                        'no_discussion' => ['label' => 'Have not discussed with household yet', 'points' => 0],
                    ],
                ],
            ],
        ],

        'time_care' => [
            'title' => 'Section B: Time and Daily Care',
            'icon' => '⏰',
            'description' => 'Assess daily availability, exercise routines, and ongoing companionship.',
            'questions' => [
                'q6_daily_hours' => [
                    'number' => 6,
                    'text' => 'How many hours per day can you realistically spend caring for and interacting with this pet?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        '4_plus' => ['label' => '4 or more hours daily (work from home or flexible)', 'points' => 5],
                        '2_to_4' => ['label' => '2 to 4 hours daily dedicated to care and play', 'points' => 5],
                        '1_to_2' => ['label' => '1 to 2 hours daily', 'points' => 3],
                        'less_1' => ['label' => 'Less than 1 hour daily', 'points' => 1],
                    ],
                ],
                'q7_daily_care_commitment' => [
                    'number' => 7,
                    'text' => 'Can you provide suitable food, fresh water, hygiene, and daily attention consistently?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'guaranteed' => ['label' => 'Yes, guaranteed daily routine without exception', 'points' => 5],
                        'shared' => ['label' => 'Yes, shared responsibilities among family members', 'points' => 4],
                        'mostly' => ['label' => 'Mostly, but might need reminders or assistance', 'points' => 3],
                        'unsure' => ['label' => 'Uncertain about maintaining consistent routine', 'points' => 1],
                    ],
                ],
                'q8_exercise_playtime' => [
                    'number' => 8,
                    'text' => 'Can you provide appropriate exercise, playtime, and mental stimulation?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'enthusiastic' => ['label' => 'Yes, daily walks, enrichment toys, and structured play', 'points' => 5],
                        'regular' => ['label' => 'Yes, moderate regular exercise according to breed needs', 'points' => 4],
                        'occasional' => ['label' => 'Occasional playtime as schedule allows', 'points' => 2],
                        'minimal' => ['label' => 'Minimal exercise capabilities', 'points' => 1],
                    ],
                ],
                'q9_travel_backup_care' => [
                    'number' => 9,
                    'text' => 'Who will care for the pet when you travel or are temporarily unavailable?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'family_friends' => ['label' => 'Trusted family members or friends ready to help', 'points' => 5],
                        'professional_boarding' => ['label' => 'Vetted professional pet sitter or boarding facility', 'points' => 5],
                        'travel_along' => ['label' => 'Plan to travel with pet whenever possible', 'points' => 4],
                        'no_plan_yet' => ['label' => 'No backup arrangement established yet', 'points' => 1],
                    ],
                ],
                'q10_training_preparedness' => [
                    'number' => 10,
                    'text' => 'Are you prepared to handle training, adjustment, and potential behavioural challenges?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'patient_positive' => ['label' => 'Yes, patient positive-reinforcement training & willing to hire a professional if needed', 'points' => 5],
                        'willing_learn' => ['label' => 'Yes, eager to learn and follow shelter guidelines', 'points' => 4],
                        'basic_only' => ['label' => 'Can handle minor issues, but prefer an already trained pet', 'points' => 2],
                        'not_prepared' => ['label' => 'Not prepared for behavioural challenges', 'points' => 0],
                    ],
                ],
            ],
        ],

        'financial_medical' => [
            'title' => 'Section C: Financial and Medical Readiness',
            'icon' => '🩺',
            'description' => 'Review budgeting for veterinary care, nutrition, and unforeseen emergencies.',
            'questions' => [
                'q11_afford_supplies' => [
                    'number' => 11,
                    'text' => 'Can you comfortably afford high-quality food, bedding, grooming, and basic supplies?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'fully_budgeted' => ['label' => 'Yes, comfortably budgeted in my monthly finances', 'points' => 6],
                        'manageable' => ['label' => 'Yes, manageable with reasonable care', 'points' => 5],
                        'tight_budget' => ['label' => 'Budget is tight, need low-cost options', 'points' => 2],
                        'uncertain' => ['label' => 'Uncertain about ongoing food & supply costs', 'points' => 0],
                    ],
                ],
                'q12_routine_vet_budget' => [
                    'number' => 12,
                    'text' => 'Can you budget for routine vaccinations, annual veterinary check-ups, and flea/tick preventatives?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'committed_routine' => ['label' => 'Yes, dedicated budget for routine vet visits and preventative medicine', 'points' => 6],
                        'manageable' => ['label' => 'Yes, will plan for yearly veterinary check-ups', 'points' => 5],
                        'only_if_sick' => ['label' => 'Will only take pet to vet if seriously sick', 'points' => 2],
                        'cannot_afford' => ['label' => 'Cannot comfortably afford regular vet visits', 'points' => 0],
                    ],
                ],
                'q13_emergency_vet_plan' => [
                    'number' => 13,
                    'text' => 'Do you have a realistic plan for unexpected veterinary expenses or medical emergencies?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'insurance_savings' => ['label' => 'Yes, pet health insurance policy or dedicated emergency savings fund', 'points' => 7],
                        'credit_support' => ['label' => 'Yes, emergency credit line or family financial support', 'points' => 5],
                        'will_figure_out' => ['label' => 'No specific savings, will figure it out if it happens', 'points' => 2],
                        'no_plan' => ['label' => 'No emergency funds or safety net', 'points' => 0],
                    ],
                ],
                'q14_vet_compliance' => [
                    'number' => 14,
                    'text' => 'Are you willing to follow veterinary advice, including vaccinations, sterilisation, and dental care?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'fully_compliant' => ['label' => 'Yes, 100% committed to following professional veterinary guidance', 'points' => 6],
                        'mostly_compliant' => ['label' => 'Yes, generally follow veterinary recommendations', 'points' => 5],
                        'hesitant' => ['label' => 'Hesitant on some procedures (e.g. sterilisation)', 'points' => 2],
                        'reluctant' => ['label' => 'Prefer not to follow standard veterinary treatments', 'points' => 0],
                    ],
                ],
            ],
        ],

        'commitment_safety' => [
            'title' => 'Section D: Long-Term Commitment and Safety',
            'icon' => '🤝',
            'description' => 'Lifespan dedication, household transitions, and safety precautions.',
            'questions' => [
                'q15_lifespan_commitment' => [
                    'number' => 15,
                    'text' => 'Are you prepared to care for this pet throughout its entire natural lifespan (10 to 18+ years)?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'lifelong_ready' => ['label' => 'Yes, fully prepared for lifelong commitment regardless of life stages', 'points' => 7],
                        'committed_years' => ['label' => 'Yes, committed for foreseeable future and will plan accordingly', 'points' => 5],
                        'short_term' => ['label' => 'Looking for short-to-medium term companionship only', 'points' => 1],
                        'not_sure' => ['label' => 'Unsure how long I can keep the pet', 'points' => 0],
                    ],
                ],
                'q16_responsibility_understanding' => [
                    'number' => 16,
                    'text' => 'Do you understand that adoption requires ongoing daily responsibility, patience, and compassion?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'thoroughly_understand' => ['label' => 'Yes, I understand pets are family members with emotional and physical needs', 'points' => 6],
                        'mostly_understand' => ['label' => 'Yes, I understand the general responsibilities involved', 'points' => 5],
                        'somewhat' => ['label' => 'Somewhat, might need advice on pet care', 'points' => 2],
                        'unsure' => ['label' => 'Have not thought much about daily responsibilities', 'points' => 0],
                    ],
                ],
                'q17_existing_pets_intro' => [
                    'number' => 17,
                    'text' => 'If you already have pets, can you introduce the new pet safely and gradually?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'safe_intro_plan' => ['label' => 'Yes, familiar with gradual intro protocols / neutral territory introductions', 'points' => 6],
                        'no_other_pets' => ['label' => 'Not applicable (no other pets currently at home)', 'points' => 6],
                        'will_research' => ['label' => 'Will research guidelines and follow shelter advice', 'points' => 4],
                        'immediate_mix' => ['label' => 'Plan to introduce immediately without separation period', 'points' => 1],
                    ],
                ],
                'q18_circumstance_change_plan' => [
                    'number' => 18,
                    'text' => 'If your living, career, or family circumstances change, do you have a responsible plan to continue caring for the pet?',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        'pet_stays_always' => ['label' => 'Pet stays with me no matter what; will find pet-friendly housing and adjust', 'points' => 6],
                        'dedicated_family' => ['label' => 'Strong family safety net to assist if circumstances shift', 'points' => 5],
                        'might_rehome' => ['label' => 'Would try to find a friend or relative to rehome', 'points' => 2],
                        'surrender' => ['label' => 'Would likely surrender to shelter if moving or changing jobs', 'points' => 0],
                    ],
                ],
                'q19_pet_experience' => [
                    'number' => 19,
                    'text' => 'What is your previous experience with caring for pets?',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'experienced_lifetime' => ['label' => 'Experienced pet owner (cared for pets throughout their life)', 'points' => 0], // Contextual
                        'grew_up_with' => ['label' => 'Grew up with family pets, familiar with basics', 'points' => 0],
                        'first_time_researched' => ['label' => 'First-time pet parent, but have researched extensively', 'points' => 0],
                        'complete_beginner' => ['label' => 'Complete beginner, looking for guidance', 'points' => 0],
                    ],
                ],
                'q20_notes' => [
                    'number' => 20,
                    'text' => 'Special arrangements, routines, or questions for our adoption team (optional):',
                    'type' => 'textarea',
                    'required' => false,
                    'options' => [],
                ],
            ],
        ],
    ];
}

/**
 * Server-side score calculator and recommendation engine
 * Returns array:
 * [
 *   'score' => float (0 - 100),
 *   'category' => string,
 *   'badge_class' => string,
 *   'strengths' => array,
 *   'improvements' => array,
 *   'recommendations' => array,
 *   'section_scores' => array,
 *   'answers_keyed' => array
 * ]
 */
function calculate_eligibility_score(array $submitted_answers, ?array $pet_info = null): array {
    $definition = get_questionnaire_definition();
    
    $raw_points = 0;
    // Maximum possible raw points:
    // Section A: 5 questions x 5 pts = 25
    // Section B: 5 questions x 5 pts = 25
    // Section C: 6 + 6 + 7 + 6 = 25
    // Section D: 7 + 6 + 6 + 6 = 25
    // Total max = 100 points
    
    $section_scores = [
        'environment' => 0,
        'time_care' => 0,
        'financial_medical' => 0,
        'commitment_safety' => 0,
    ];

    $strengths = [];
    $improvements = [];
    $recommendations = [];
    $answers_keyed = [];

    foreach ($definition as $section_key => $section) {
        foreach ($section['questions'] as $qkey => $qdef) {
            $user_val = trim($submitted_answers[$qkey] ?? '');
            
            // Record submitted answer text for persistence
            $selected_label = $user_val;
            if (isset($qdef['options'][$user_val])) {
                $selected_label = $qdef['options'][$user_val]['label'];
                $pts = (int)($qdef['options'][$user_val]['points'] ?? 0);
                $raw_points += $pts;
                $section_scores[$section_key] += $pts;
            }

            $answers_keyed[$qkey] = [
                'number' => $qdef['number'],
                'question_text' => $qdef['text'],
                'raw_value' => $user_val,
                'display_answer' => $selected_label ?: '(Not specified)',
            ];
        }
    }

    // Ensure score is clamped between 0 and 100
    $score = max(0, min(100, (float)$raw_points));

    // Evaluate Strengths
    if ($section_scores['environment'] >= 22) {
        $strengths[] = 'Superb living environment and unanimous household agreement.';
    }
    if ($section_scores['time_care'] >= 22) {
        $strengths[] = 'Generous daily time dedication and structured backup care plans.';
    }
    if ($section_scores['financial_medical'] >= 22) {
        $strengths[] = 'Outstanding financial readiness and proactive veterinary emergency plan.';
    }
    if ($section_scores['commitment_safety'] >= 22) {
        $strengths[] = 'Deep commitment to lifelong companionship and responsible care.';
    }
    if (empty($strengths)) {
        $strengths[] = 'Demonstrates genuine enthusiasm and interest in animal companionship.';
    }

    // Evaluate Improvement Areas & Recommendations
    $landlord_ans = $submitted_answers['q1_landlord_permission'] ?? '';
    if (in_array($landlord_ans, ['in_progress', 'not_yet'])) {
        $improvements[] = 'Landlord or housing association approval is pending or not yet confirmed.';
        $recommendations[] = 'Obtain written confirmation or pet agreement addendum from your landlord/HOA before trial adoption.';
    }

    $backup_ans = $submitted_answers['q9_travel_backup_care'] ?? '';
    if ($backup_ans === 'no_plan_yet') {
        $improvements[] = 'Backup caregiver arrangement during travel or emergencies has not been established.';
        $recommendations[] = 'Connect with local pet sitters, trusted neighbors, or reputable boarding kennels prior to bringing pet home.';
    }

    $emergency_ans = $submitted_answers['q13_emergency_vet_plan'] ?? '';
    if (in_array($emergency_ans, ['will_figure_out', 'no_plan'])) {
        $improvements[] = 'Emergency veterinary safety net is minimal or unplanned.';
        $recommendations[] = 'Explore pet health insurance policies or start a dedicated emergency medical savings fund ($500-$1000 buffer).';
    }

    $hours_ans = $submitted_answers['q6_daily_hours'] ?? '';
    if (in_array($hours_ans, ['1_to_2', 'less_1'])) {
        $improvements[] = 'Limited daily dedicated hours for exercise and direct interaction.';
        $recommendations[] = 'Consider animals with lower activity requirements or arrange midday dog-walking assistance.';
    }

    $training_ans = $submitted_answers['q10_training_preparedness'] ?? '';
    if (in_array($training_ans, ['basic_only', 'not_prepared'])) {
        $recommendations[] = 'Review positive reinforcement guides and basic crate/leash manners resources recommended by our shelter.';
    }

    if (empty($recommendations)) {
        $recommendations[] = 'Keep all current vaccination schedules, arrange initial wellness check with your veterinarian, and enjoy bonding!';
    }

    // Categorization
    if ($score >= 85) {
        $category = 'Highly Recommended';
        $badge_class = 'badge-success';
    } elseif ($score >= 70) {
        $category = 'Eligible - Strong Match';
        $badge_class = 'badge-primary';
    } elseif ($score >= 50) {
        $category = 'Conditional - Review Needed';
        $badge_class = 'badge-warning';
    } else {
        $category = 'Needs Additional Preparation';
        $badge_class = 'badge-danger';
    }

    return [
        'score' => $score,
        'category' => $category,
        'badge_class' => $badge_class,
        'strengths' => $strengths,
        'improvements' => $improvements,
        'recommendations' => $recommendations,
        'section_scores' => $section_scores,
        'answers_keyed' => $answers_keyed,
    ];
}
