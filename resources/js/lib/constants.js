export const COUNTRIES = [
    { code: 'PK', name: 'Pakistan', flag: '🇵🇰', tier: 'free' },
    { code: 'GB', name: 'UK',       flag: '🇬🇧', tier: 'starter' },
    { code: 'US', name: 'USA',      flag: '🇺🇸', tier: 'professional' },
    { code: '*',  name: 'Global',   flag: '🌍',  tier: 'professional' },
];

export const SOURCES = [
    { slug: 'ppra_federal', label: 'PPRA Federal',        country: 'PK', tier: 'free' },
    { slug: 'sppra',        label: 'SPPRA (Sindh)',        country: 'PK', tier: 'free' },
    { slug: 'kppra',        label: 'KPPRA (KPK)',          country: 'PK', tier: 'free' },
    { slug: 'bppra',        label: 'BPPRA (Baloch)',       country: 'PK', tier: 'free' },
    { slug: 'uk_fts',       label: 'UK Find a Tender',     country: 'GB', tier: 'starter' },
    { slug: 'uk_cf',        label: 'UK Contracts Finder',  country: 'GB', tier: 'starter' },
    { slug: 'world_bank',   label: 'World Bank',           country: '*',  tier: 'professional' },
    { slug: 'sam_gov',      label: 'SAM.gov (USA)',        country: 'US', tier: 'professional' },
    { slug: 'ungm',         label: 'UN (UNGM)',            country: '*',  tier: 'professional' },
    { slug: 'adb',          label: 'ADB',                  country: '*',  tier: 'professional' },
    { slug: 'afdb',         label: 'AfDB',                 country: '*',  tier: 'professional' },
];

export const CURRENCY_SYMBOLS = {
    PKR: 'PKR',
    AED: 'AED',
    SAR: 'SAR',
    USD: 'USD',
};

export const CATEGORIES = [
    'Goods',
    'Works',
    'Consultancy Services',
    'Non-Consultancy Services',
];

export const SECTORS = [
    'Agriculture',
    'Civil Works',
    'Education',
    'Electrical',
    'Environment',
    'Health/Medicines',
    'ICT/Software',
    'Infrastructure',
    'Oil & Gas',
    'Security',
    'Transport',
    'Water & Sanitation',
    'Other',
];

export const CITIES = [
    'Islamabad',
    'Karachi',
    'Lahore',
    'Peshawar',
    'Quetta',
    'Rawalpindi',
    'Faisalabad',
    'Multan',
    'Hyderabad',
    'Sialkot',
    'Gujranwala',
    'Abbottabad',
    'Muzaffarabad',
    'Gilgit',
];

export const TENDER_TYPES = [
    'Tender Notice',
    'RFP',
    'EOI',
    'PQ',
];

export const PLAN_LIMITS = {
    free: {
        dailyViews:      5,
        alerts:          1,
        summaries:       false,
        pdfs:            false,
        recommendations: false,
        export:          false,
        apiCalls:        0,
        countries:       ['PK'],
        price:           0,
    },
    starter: {
        dailyViews:      Infinity,
        alerts:          5,
        summaries:       true,
        pdfs:            false,
        recommendations: false,
        export:          true,
        apiCalls:        0,
        countries:       ['PK', 'GB'],
        price:           29,
    },
    professional: {
        dailyViews:      Infinity,
        alerts:          20,
        summaries:       true,
        pdfs:            true,
        recommendations: true,
        export:          true,
        apiCalls:        0,
        countries:       ['PK', 'GB', 'US', '*'],
        price:           49,
    },
    enterprise: {
        dailyViews:      Infinity,
        alerts:          Infinity,
        summaries:       true,
        pdfs:            true,
        recommendations: true,
        export:          true,
        apiCalls:        1000,
        countries:       ['PK', 'GB', 'US', '*'],
        price:           99,
    },
};

export const STATUS_VARIANTS = {
    Published:   'published',
    Corrigendum: 'corrigendum',
    Cancelled:   'cancelled',
};

export const CATEGORY_VARIANTS = {
    'Goods':                    'goods',
    'Works':                    'works',
    'Consultancy Services':     'consultancy',
    'Non-Consultancy Services': 'non-consultancy',
};

export const RECOMMENDATION_VARIANTS = {
    Apply:  'apply',
    Review: 'review',
    Skip:   'skip',
};
