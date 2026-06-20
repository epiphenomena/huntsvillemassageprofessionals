<?php
/**
 * Canonical content data: therapists, services and packages.
 *
 * Names, licenses and specialties are drawn from the live site and used as a
 * proof-of-concept. Photos are placeholders (assets/img/therapists/<slug>.svg)
 * and can be swapped for real headshots later without touching markup.
 */

declare(strict_types=1);

/** @return array<int,array<string,mixed>> */
function hmp_therapists(): array
{
    return [
        [
            'slug' => 'karmen-scruggs', 'name' => 'Karmen Scruggs', 'license' => 'AL #2471',
            'title' => 'LMT · Ashiatsu Specialist',
            'specialties' => ['Ashiatsu', 'Relaxation', 'Hot Stone', 'Deep Tissue', 'Prenatal', 'Thai Foot Massage'],
            'bio' => 'With over 18 years of experience, Karmen blends barefoot Ashiatsu with classic relaxation and deep tissue work to deliver deeply effective, customized sessions.',
        ],
        [
            'slug' => 'rebecca-horton', 'name' => 'Rebecca Horton', 'license' => 'AL #706',
            'title' => 'LMT · Medi-Cupping & MPS',
            'specialties' => ['Deep Tissue', 'Stretching', 'Medi-Cupping', 'Microcurrent Point Stimulation'],
            'bio' => 'Rebecca specializes in advanced therapeutic techniques including medi-cupping and Microcurrent Point Stimulation (MPS) for pain relief and recovery.',
        ],
        [
            'slug' => 'shauna-gilley', 'name' => 'Shauna Gilley', 'license' => 'AL #3335',
            'title' => 'LMT · Trigger Point & Cupping',
            'specialties' => ['Medi-Cupping', 'Deep Tissue', 'Prenatal', 'Relaxation', 'Hot Stone', 'Trigger Point Therapy'],
            'bio' => 'Shauna combines trigger point therapy and medi-cupping with nurturing prenatal and relaxation work to address both tension and total-body wellness.',
        ],
        [
            'slug' => 'jordan-lawles', 'name' => 'Jordan Lawles', 'license' => 'AL #3617',
            'title' => 'LMT · Ashiatsu Specialist',
            'specialties' => ['Ashiatsu', 'Deep Tissue', 'Relaxation'],
            'bio' => 'An Ashiatsu specialist with over 11 years of experience, Jordan uses gravity-assisted barefoot technique to deliver broad, deep, and incredibly relaxing pressure.',
        ],
        [
            'slug' => 'jodie-longino', 'name' => 'Jodie Longino', 'license' => 'AL #5977',
            'title' => 'LMT · Manual Lymph Drainage',
            'specialties' => ['Relaxation', 'Hot Stone', 'Deep Tissue', 'Prenatal', 'Manual Lymph Drainage'],
            'bio' => 'Certified in manual lymph drainage, Jodie offers gentle, restorative sessions alongside classic relaxation, hot stone, and prenatal massage.',
        ],
        [
            'slug' => 'katherine-harris', 'name' => 'Katherine Harris', 'license' => 'AL #3923',
            'title' => 'LMT · Reflexology',
            'specialties' => ['Reflexology', 'Relaxation', 'Deep Tissue', 'Hot Stone', 'Prenatal'],
            'bio' => 'Katherine specializes in reflexology, pairing targeted foot and pressure-point work with relaxation, deep tissue, and prenatal massage.',
        ],
        [
            'slug' => 'cheryl-cole', 'name' => 'Cheryl Cole', 'license' => 'AL #5702',
            'title' => 'LMT · Lymphatic Care',
            'specialties' => ['Deep Tissue', 'Hot Stone', 'Prenatal', 'Relaxation', 'Lymphatic Drainage', 'Lymphatic Facial Rejuvenation'],
            'bio' => 'Cheryl offers lymphatic drainage and lymphatic facial rejuvenation alongside therapeutic massage, including a dedicated Grief & Healing session.',
        ],
    ];
}

/** Look up a single therapist by slug. */
function hmp_therapist(string $slug): ?array
{
    foreach (hmp_therapists() as $t) {
        if ($t['slug'] === $slug) {
            return $t;
        }
    }
    return null;
}

/** Service menu grouped by category. */
function hmp_services(): array
{
    return [
        'Signature Massage' => [
            ['name' => 'Relaxation Massage', 'options' => '30 min · $60   |   60 min · $100   |   90 min · $150   |   120 min · $200'],
            ['name' => 'Deep Tissue', 'options' => '30 min · $60   |   60 min · $100   |   90 min · $150'],
            ['name' => 'Hot Stone / Heated Bamboo', 'options' => '60 min · $100   |   90 min · $150   |   120 min · $200'],
            ['name' => 'Ashiatsu Oriental Bar Therapy', 'options' => '60 min · $100   |   75 min · $125   |   90 min · $150   |   120 min · $200'],
            ['name' => 'Prenatal Massage', 'options' => '60 min · $100   |   90 min · $150'],
        ],
        'Therapeutic & Specialty' => [
            ['name' => 'Lymphatic Drainage Massage', 'options' => '60 min · $100   |   90 min · $150   |   120 min · $200'],
            ['name' => 'Lymphatic Drainage Facial Rejuvenation', 'options' => '45 min · $75   |   60 min · $100'],
            ['name' => 'Microcurrent Point Stimulation (Rebecca only)', 'options' => '30 min · $75   |   60 min · $150'],
            ['name' => 'Foot Herbology / Reflexology', 'options' => '$75'],
            ['name' => 'Thai Foot Massage', 'options' => '30 min · $65'],
        ],
        'Quick Add-Ons' => [
            ['name' => 'Ultimate Scalp Massage', 'options' => '15 min · $45'],
            ['name' => 'Cool Stone Facial Massage', 'options' => '$75'],
        ],
    ];
}

/** Spa packages. */
function hmp_packages(): array
{
    return [
        ['name' => 'A Taste of Massage', 'price' => '$185', 'desc' => 'A perfect introduction — a sampler of our most-loved techniques.'],
        ['name' => 'Ultimate Prenatal Package', 'price' => '$175', 'desc' => 'Nurturing, safe, and restorative care for expecting mothers.'],
        ['name' => 'Just For Her', 'price' => '$295', 'desc' => 'A full pampering experience designed around her.'],
        ['name' => 'Just For Him', 'price' => '$295', 'desc' => 'Targeted, restorative bodywork tailored for him.'],
        ['name' => 'Date Night Package', 'price' => '$395', 'desc' => 'Side-by-side relaxation for two.'],
        ['name' => 'Ultimate Spa Package', 'price' => '$395', 'desc' => 'The complete escape — our most indulgent experience.'],
        ['name' => 'Grief & Healing Package', 'price' => '105 min · $200', 'desc' => 'A compassionate, supportive session (with Cheryl Cole).'],
    ];
}

/** Flat list of bookable services for forms/dropdowns. */
function hmp_service_options(): array
{
    return [
        'Relaxation Massage', 'Deep Tissue', 'Hot Stone / Heated Bamboo',
        'Ashiatsu Oriental Bar Therapy', 'Prenatal Massage', 'Lymphatic Drainage Massage',
        'Lymphatic Drainage Facial Rejuvenation', 'Microcurrent Point Stimulation',
        'Foot Herbology / Reflexology', 'Thai Foot Massage', 'Ultimate Scalp Massage',
        'Cool Stone Facial Massage', 'Other / Not sure yet',
    ];
}

/** Medical conditions checklist for the digital intake form. */
function hmp_conditions(): array
{
    return [
        'Skin problems / rashes', 'Circulatory problems', 'Varicose veins', 'HIV/AIDS',
        'Cardiac problems', 'High blood pressure', 'Frequent headaches', 'Respiratory problems',
        'Epilepsy / seizures', 'Osteoporosis', 'Pancreatitis', 'Depression / anxiety',
        'Asthma', 'Bruise easily', "Athlete's foot", 'Hepatitis', 'Cancer', 'Diabetes',
    ];
}
