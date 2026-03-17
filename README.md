# Mason Construction Services Inc – Website

Official website for **Mason Construction Services Inc**, a licensed masonry and general contracting company serving New York City and the surrounding area for over 20 years.

Live site: [https://themasonconstruction.com](https://themasonconstruction.com)

---

## Table of Contents

- [Project Overview](#project-overview)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [File Structure](#file-structure)
- [Running the Website Locally](#running-the-website-locally)
- [Build Process](#build-process)
- [Development Guidelines](#development-guidelines)
- [Responsive Design](#responsive-design)
- [Deployment](#deployment)
- [Contributing](#contributing)
- [License](#license)

---

## Project Overview

This is a static HTML5 website built with:

- **HTML5** – semantic page structure and markup
- **CSS3** – custom styles (`css/styles.css`) plus Bootstrap 4 via `assets/css/style-liberty.css`
- **JavaScript** – jQuery 3.3.1, Bootstrap JS, Owl Carousel, Lightbox, and a dark/light theme switcher
- **Formspree** – serverless contact form submission

### Pages

| File | Description |
|------|-------------|
| `index.html` | Home page – hero slider, services, projects gallery, FAQ |
| `about.html` | Company background and team |
| `services.html` | Full list of services offered |
| `contact.html` | Contact form and Google Maps embed |
| `masonry.html` | Masonry project portfolio |
| `concrete.html` | Concrete project portfolio |
| `interior.html` | Interior fit-out project portfolio |
| `roofing.html` | Roofing project portfolio |
| `blog.html` | Blog listing |
| `blog-single.html` | Individual blog post template |
| `landing-single.html` | Alternate landing page |

---

## Prerequisites

- **Node.js** ≥ 16 and **npm** ≥ 8 (only required for the build/minification step)
- Any modern web browser (Chrome, Firefox, Safari, Edge)
- A local static file server (optional – see [Running Locally](#running-the-website-locally))

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/shindeaditya7/Mason-Construction-website.git
cd Mason-Construction-website

# 2. Install development dependencies (optional – only needed for CSS minification)
npm install
```

---

## File Structure

```
Mason-Construction-website/
├── index.html               # Home page
├── about.html
├── services.html
├── contact.html
├── masonry.html
├── concrete.html
├── interior.html
├── roofing.html
├── blog.html
├── blog-single.html
├── landing-single.html
├── package.json             # npm scripts for build tooling
├── css/
│   ├── styles.css           # Main stylesheet (source)
│   └── styles.min.css       # Minified stylesheet (generated)
├── assets/
│   ├── css/
│   │   └── style-liberty.css  # Bootstrap 4 + template base styles
│   ├── js/
│   │   ├── jquery-3.3.1.min.js
│   │   ├── bootstrap.min.js
│   │   ├── owl.carousel.min.js
│   │   ├── lightbox.min.js
│   │   ├── theme-change.js
│   │   └── ...
│   ├── images/              # All site images (JPEG / WebP)
│   │   ├── projects/
│   │   │   ├── masonry_final/
│   │   │   ├── concrete_final/
│   │   │   ├── Interior work/
│   │   │   └── waterproofing_final/
│   │   └── masonary/
│   └── mp4/
│       └── bg.mp4           # Hero background video
└── README.md
```

---

## Running the Website Locally

Because the site uses absolute asset paths and a video background, it works best served through a local HTTP server rather than opened directly as a file.

### Option 1 – npm `start` script (uses `serve`)

```bash
npm start
# Opens http://localhost:3000
```

### Option 2 – npm `dev` script (uses `live-server` with auto-reload)

```bash
npm run dev
# Opens http://localhost:8080 and auto-reloads on file changes
```

### Option 3 – Python (no install required)

```bash
python3 -m http.server 8080
# Visit http://localhost:8080
```

### Option 4 – VS Code Live Server extension

Install the **Live Server** extension in VS Code, right-click `index.html`, and choose **Open with Live Server**.

---

## Build Process

### CSS Minification

The source stylesheet is `css/styles.css`. A minified version (`css/styles.min.css`) is generated with [clean-css-cli](https://github.com/clean-css/clean-css-cli):

```bash
npm run build:css
```

This reduces file size from ~235 KB to ~192 KB.

To run the full build (currently only CSS minification):

```bash
npm run build
```

> **Note:** The HTML files currently reference `assets/css/style-liberty.css`. If you switch to the minified file, update the `<link>` tags in each HTML file to point to `css/styles.min.css`.

---

## Development Guidelines

1. **Keep HTML semantic** – use proper heading hierarchy (`h1` → `h2` → `h3` …) and ARIA labels.
2. **Images** – prefer WebP format for photos; keep JPEG for the logo.
3. **JavaScript** – custom scripts go in `js/scripts.js`; avoid inline `<script>` blocks for new features.
4. **CSS** – edit `css/styles.css` (the source file), then run `npm run build:css` to regenerate the minified version.
5. **Links** – all internal page links use relative paths (e.g. `href="index.html"`, not `href="#index.html"`).
6. **Forms** – the contact form posts to [Formspree](https://formspree.io/). Client-side validation is handled in `contact.html` via a small inline script.

---

## Responsive Design

The site uses Bootstrap's grid and is tested at these breakpoints:

| Breakpoint | Width |
|------------|-------|
| Extra-small (mobile) | < 576 px |
| Small (landscape phone) | ≥ 576 px |
| Medium (tablet) | ≥ 768 px |
| Large (desktop) | ≥ 992 px |
| Extra-large | ≥ 1200 px |

**Tested browsers:** Chrome 120+, Firefox 121+, Safari 17+, Edge 120+.

All pages include the standard viewport meta tag:
```html
<meta name="viewport" content="width=device-width, initial-scale=1">
```

---

## Deployment

This is a static website and can be deployed to any static hosting provider:

### GitHub Pages

1. Push to `main` branch.
2. Go to **Settings → Pages**, set source to `main` / `root`.
3. The site will be available at `https://<username>.github.io/Mason-Construction-website/`.

### Netlify / Vercel

1. Connect the GitHub repository.
2. Set **build command** to `npm run build` (optional, for CSS minification).
3. Set **publish directory** to `.` (root).

### Traditional Web Host (FTP/cPanel)

Upload all files (excluding `node_modules/`) to the `public_html` directory.

---

## Contributing

1. Fork the repository and create a feature branch (`git checkout -b feature/my-change`).
2. Make your changes, following the [Development Guidelines](#development-guidelines) above.
3. Run `npm run build` to regenerate minified assets if you edited CSS.
4. Open a pull request describing what you changed and why.

---

## License

This website and its content are proprietary to **Mason Construction Services Inc**. The Bootstrap template base (`assets/css/style-liberty.css`) is licensed under the W3Layouts license – see `liberty-license-W3Layouts.txt` for details.

