import { useEffect, useMemo, useState } from 'react'
import { Link, NavLink, Route, Routes, useLocation } from 'react-router-dom'
import {
  ArrowRight, BookOpen, CalendarDays, Check, ChevronDown, CirclePlay,
  Clock3, GraduationCap, LayoutDashboard, Library, LockKeyhole, Menu,
  MessageCircle, MonitorPlay, Play, Search, ShieldCheck, Sparkles, Star,
  Target, UserRound, Users, X,
} from 'lucide-react'

const courses = [
  {
    title: 'Foundations of the Sunnah',
    teacher: 'Ustadh Yasin Munye',
    category: 'Aqeedah',
    level: 'Beginner',
    lessons: 33,
    progress: 0,
    image: 'https://images.unsplash.com/photo-1586767003402-8ade266deb64?auto=format&fit=crop&w=1200&q=85',
    tone: 'teal',
  },
  {
    title: 'The Forty Hadith',
    teacher: 'Shaykh Abdul Rahman Hassan',
    category: 'Hadith',
    level: 'Beginner',
    lessons: 42,
    progress: 28,
    image: 'https://images.unsplash.com/photo-1609599006353-e629aaabfeae?auto=format&fit=crop&w=1200&q=85',
    tone: 'blue',
  },
  {
    title: 'The Path to Prayer',
    teacher: 'Ustadh Muhammad Tim',
    category: 'Fiqh',
    level: 'Essential',
    lessons: 24,
    progress: 64,
    image: 'https://images.unsplash.com/photo-1591604466107-ec97de577aff?auto=format&fit=crop&w=1200&q=85',
    tone: 'green',
  },
  {
    title: 'Purification of the Heart',
    teacher: 'Shaykh Abu Hakeem',
    category: 'Spirituality',
    level: 'Intermediate',
    lessons: 18,
    progress: 0,
    image: 'https://images.unsplash.com/photo-1590075865003-e48277faa558?auto=format&fit=crop&w=1200&q=85',
    tone: 'sand',
  },
  {
    title: 'Arabic: Read With Confidence',
    teacher: 'Ustadh Abdullah Ahmed',
    category: 'Arabic',
    level: 'Beginner',
    lessons: 56,
    progress: 0,
    image: 'https://images.unsplash.com/photo-1574545640323-59dc7a2b4a6d?auto=format&fit=crop&w=1200&q=85',
    tone: 'blue',
  },
  {
    title: 'Lives of the Prophets',
    teacher: 'Ustadh Abdul Wahid',
    category: 'Seerah',
    level: 'All levels',
    lessons: 30,
    progress: 0,
    image: 'https://images.unsplash.com/photo-1542816417-0983c9c9ad53?auto=format&fit=crop&w=1200&q=85',
    tone: 'teal',
  },
]

const benefits = [
  ['Structured pathways', 'Follow a clear curriculum from foundations to deeper study.', Target],
  ['Trusted teachers', 'Learn with qualified teachers grounded in Qur’an and Sunnah.', ShieldCheck],
  ['Learn at your pace', 'Access concise lessons, notes and assessments on any device.', MonitorPlay],
]

function Logo({ light = false }) {
  return (
    <Link className={`brand ${light ? 'brand-light' : ''}`} to="/" aria-label="ASWJ Islamic College home">
      <img src="/aswj-mark.png" alt="" />
      <span><b>ASWJ</b><small>Islamic College</small></span>
    </Link>
  )
}

function Header() {
  const [open, setOpen] = useState(false)
  const location = useLocation()
  useEffect(() => setOpen(false), [location])
  const links = [['/', 'Home'], ['/courses', 'Courses'], ['/subscribe', 'Membership']]

  return (
    <>
      <div className="announcement">Enrollment is now open <span>•</span> Begin your learning journey today <Link to="/register">Join ASWJ <ArrowRight size={14} /></Link></div>
      <header className="site-header">
        <div className="container nav-wrap">
          <Logo />
          <nav className={`main-nav ${open ? 'open' : ''}`} aria-label="Main navigation">
            {links.map(([to, label]) => <NavLink key={to} to={to} end={to === '/'}>{label}</NavLink>)}
            <NavLink to="/login" className="mobile-only">Student login</NavLink>
            <Link to="/register" className="button button-primary mobile-only">Start learning</Link>
          </nav>
          <div className="nav-actions">
            <Link to="/login" className="text-link">Student login</Link>
            <Link to="/register" className="button button-primary">Start learning <ArrowRight size={16} /></Link>
            <button className="menu-button" onClick={() => setOpen(!open)} aria-label="Toggle menu">{open ? <X /> : <Menu />}</button>
          </div>
        </div>
      </header>
    </>
  )
}

function Footer() {
  return (
    <footer>
      <div className="container footer-grid">
        <div className="footer-intro">
          <Logo light />
          <p>Knowledge with clarity. Learning with purpose.</p>
          <div className="social-row"><span>YT</span><span>IG</span><span>FB</span></div>
        </div>
        <div><h4>Explore</h4><Link to="/courses">All courses</Link><Link to="/subscribe">Membership</Link><Link to="/student-portal">Student portal</Link></div>
        <div><h4>College</h4><Link to="/">Our approach</Link><Link to="/">Teachers</Link><Link to="/">Contact</Link></div>
        <div><h4>Stay informed</h4><p>Occasional updates, new courses and study advice.</p><div className="email-field"><input aria-label="Email address" placeholder="Email address" /><button aria-label="Submit email"><ArrowRight /></button></div></div>
      </div>
      <div className="container footer-base"><span>© 2026 ASWJ Islamic College</span><span>Terms · Privacy</span></div>
    </footer>
  )
}

function PageShell({ children, bare = false }) {
  return <>{!bare && <Header />}<main>{children}</main>{!bare && <Footer />}</>
}

function SectionHead({ eyebrow, title, copy, action }) {
  return (
    <div className="section-head">
      <div><span className="eyebrow">{eyebrow}</span><h2>{title}</h2>{copy && <p>{copy}</p>}</div>
      {action}
    </div>
  )
}

function CourseCard({ course, portal = false }) {
  return (
    <article className="course-card">
      <Link to={portal ? '/student-portal' : '/register'} className="course-image">
        <img src={course.image} alt="" />
        <span className={`course-badge ${course.tone}`}>{course.level}</span>
        <span className="play-float"><Play size={17} fill="currentColor" /></span>
      </Link>
      <div className="course-body">
        <div className="course-meta"><span>{course.category}</span><span><BookOpen size={14} /> {course.lessons} lessons</span></div>
        <h3><Link to={portal ? '/student-portal' : '/register'}>{course.title}</Link></h3>
        <p>{course.teacher}</p>
        {portal && course.progress > 0 ? <><div className="progress-label"><span>{course.progress}% complete</span><b>Continue</b></div><div className="progress"><i style={{ width: `${course.progress}%` }} /></div></> : <Link className="card-link" to="/register">View course <ArrowRight size={16} /></Link>}
      </div>
    </article>
  )
}

function Home() {
  return (
    <PageShell>
      <section className="hero">
        <div className="hero-pattern" />
        <div className="container hero-grid">
          <div className="hero-copy reveal">
            <span className="eyebrow light">A considered path to knowledge</span>
            <h1>Learn your religion with <em>clarity</em> and confidence.</h1>
            <p>Structured online Islamic education, taught by trusted teachers and designed around the life you already lead.</p>
            <div className="hero-actions"><Link className="button button-accent" to="/courses">Explore courses <ArrowRight size={17} /></Link><Link className="watch-link" to="/"><CirclePlay /> See how it works</Link></div>
            <div className="trust-row"><div className="avatars"><span>YA</span><span>AM</span><span>SA</span></div><div><span className="stars">★★★★★</span><small>Trusted by 2,000+ learners</small></div></div>
          </div>
          <div className="hero-visual reveal delay">
            <div className="arch-frame"><img src="https://images.unsplash.com/photo-1564769625905-50e93615e769?auto=format&fit=crop&w=1400&q=88" alt="Student reading and studying" /></div>
            <div className="floating-card lesson-float"><span><Play fill="currentColor" /></span><div><small>Continue learning</small><b>The Book of Knowledge</b><i><em style={{ width: '62%' }} /></i></div></div>
            <div className="floating-card stat-float"><b>120+</b><span>guided lessons</span></div>
          </div>
        </div>
      </section>

      <section className="principles">
        <div className="container benefits-grid">
          {benefits.map(([title, copy, Icon], i) => <div className="benefit" key={title}><span>0{i + 1}</span><Icon /><div><h3>{title}</h3><p>{copy}</p></div></div>)}
        </div>
      </section>

      <section className="section">
        <div className="container">
          <SectionHead eyebrow="Begin your studies" title="Courses with a clear purpose." copy="Build strong foundations through focused, accessible programmes." action={<Link className="button button-outline" to="/courses">Browse all courses <ArrowRight size={16} /></Link>} />
          <div className="course-grid">{courses.slice(0, 3).map(course => <CourseCard course={course} key={course.title} />)}</div>
        </div>
      </section>

      <section className="section feature-band">
        <div className="container feature-grid">
          <div className="feature-image"><img src="https://images.unsplash.com/photo-1553484771-371a605b060b?auto=format&fit=crop&w=1400&q=85" alt="Teacher presenting a lesson" /><span className="quote-chip"><Star fill="currentColor" /> “Accessible, grounded and easy to follow.”</span></div>
          <div className="feature-copy">
            <span className="eyebrow">Learning that stays with you</span>
            <h2>Depth without the overwhelm.</h2>
            <p>We turn substantial subjects into calm, considered learning experiences—so you can understand, retain and act upon what you learn.</p>
            <ul>{['Short, focused video lessons', 'Downloadable notes and key takeaways', 'Study pathways that build in sequence'].map(x => <li key={x}><Check />{x}</li>)}</ul>
            <Link className="text-arrow" to="/subscribe">Discover the ASWJ approach <ArrowRight /></Link>
          </div>
        </div>
      </section>

      <Teachers />
      <Testimonial />
      <CTA />
    </PageShell>
  )
}

function Teachers() {
  const people = [
    ['Ustadh Yasin Munye', 'Aqeedah & Manhaj', 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=700&q=80'],
    ['Ustadh Abdul Wahid', 'Seerah & History', 'https://images.unsplash.com/photo-1615109398623-88346a601842?auto=format&fit=crop&w=700&q=80'],
    ['Ustadh Muhammad Tim', 'Fiqh & Worship', 'https://images.unsplash.com/photo-1531384698654-7f6e477ca221?auto=format&fit=crop&w=700&q=80'],
  ]
  return (
    <section className="section teachers-section"><div className="container">
      <SectionHead eyebrow="Your teachers" title="Learn from people you can trust." copy="Thoughtful teaching. Authentic sources. A sincere commitment to student growth." />
      <div className="teacher-grid">{people.map(([name, role, image]) => <article className="teacher-card" key={name}><img src={image} alt="" /><div><h3>{name}</h3><p>{role}</p></div></article>)}</div>
    </div></section>
  )
}

function Testimonial() {
  return (
    <section className="section testimonial-section"><div className="container testimonial-grid">
      <div><span className="eyebrow light">Student stories</span><blockquote>“For the first time, my studies feel organised. I know what to learn next—and more importantly, <em>why it matters.</em>”</blockquote><div className="quote-author"><span>AK</span><div><b>Ahmed K.</b><small>ASWJ member · Birmingham</small></div></div></div>
      <div className="testimonial-image"><img src="https://images.unsplash.com/photo-1491841550275-ad7854e35ca6?auto=format&fit=crop&w=1200&q=85" alt="Student studying with a book" /><button aria-label="Play student story"><Play fill="currentColor" /></button></div>
    </div></section>
  )
}

function CTA() {
  return <section className="cta-wrap"><div className="container"><div className="cta-card"><span className="eyebrow light">Start today</span><h2>A lifetime of learning begins with one lesson.</h2><p>Join the ASWJ community and study with direction, consistency and confidence.</p><Link to="/register" className="button button-accent">Begin your journey <ArrowRight /></Link></div></div></section>
}

function Courses() {
  const [query, setQuery] = useState('')
  const [category, setCategory] = useState('All')
  const filtered = useMemo(() => courses.filter(c => (category === 'All' || c.category === category) && c.title.toLowerCase().includes(query.toLowerCase())), [query, category])
  return (
    <PageShell>
      <section className="page-hero compact"><div className="container"><span className="eyebrow light">The course library</span><h1>Knowledge, thoughtfully <em>structured.</em></h1><p>Explore courses designed to strengthen your foundations and deepen your understanding.</p></div></section>
      <section className="section course-library"><div className="container">
        <div className="filter-bar"><label className="search-box"><Search /><input value={query} onChange={e => setQuery(e.target.value)} placeholder="Search courses" /></label><div className="filter-select"><span>Level: All</span><ChevronDown /></div><div className="filter-select"><span>Sort: Recommended</span><ChevronDown /></div></div>
        <div className="chips">{['All', ...new Set(courses.map(c => c.category))].map(x => <button className={category === x ? 'active' : ''} onClick={() => setCategory(x)} key={x}>{x}</button>)}</div>
        <div className="results-head"><h2>{category === 'All' ? 'All courses' : category}</h2><span>{filtered.length} courses</span></div>
        <div className="course-grid">{filtered.map(course => <CourseCard course={course} key={course.title} />)}</div>
      </div></section>
      <CTA />
    </PageShell>
  )
}

function Subscribe() {
  const [annual, setAnnual] = useState(true)
  const features = ['Complete course library', 'New lessons every month', 'Downloadable study notes', 'Assessments and certificates', 'Members’ study community', 'Learn on any device']
  return (
    <PageShell>
      <section className="page-hero pricing-hero"><div className="container center"><span className="eyebrow light">Simple membership</span><h1>Invest in knowledge that <em>lasts.</em></h1><p>Everything you need to build a consistent, meaningful study practice.</p><div className="billing-toggle"><button className={!annual ? 'active' : ''} onClick={() => setAnnual(false)}>Monthly</button><button className={annual ? 'active' : ''} onClick={() => setAnnual(true)}>Yearly <span>Save 20%</span></button></div></div></section>
      <section className="pricing-section"><div className="container pricing-grid">
        <article className="price-card">
          <span className="plan-icon"><BookOpen /></span><small>ASWJ Membership</small><h2>{annual ? '£8' : '£10'}<span>/ month</span></h2><p>{annual ? '£96 billed once per year' : 'Flexible monthly billing'}</p>
          <Link className="button button-primary full" to="/register">Start learning today <ArrowRight /></Link>
          <ul>{features.map(x => <li key={x}><Check />{x}</li>)}</ul>
          <div className="guarantee"><ShieldCheck /><span><b>7-day money-back promise</b><small>Explore the platform with confidence.</small></span></div>
        </article>
        <aside className="pricing-aside"><span className="eyebrow">Included in your membership</span><h2>One calm space for serious study.</h2>
          <div className="mini-feature"><MonitorPlay /><div><b>120+ guided lessons</b><span>Across foundational Islamic subjects</span></div></div>
          <div className="mini-feature"><GraduationCap /><div><b>Structured study pathways</b><span>Always know what to learn next</span></div></div>
          <div className="mini-feature"><Users /><div><b>Growing learner community</b><span>Study alongside purposeful students</span></div></div>
          <div className="quote-small">“The value is not just in the volume of content—it’s in how carefully everything has been organised.”<b>— ASWJ student</b></div>
        </aside>
      </div></section>
      <section className="section faq"><div className="container narrow"><SectionHead eyebrow="Questions, answered" title="Before you begin." />{['Can I cancel at any time?', 'Is this suitable for complete beginners?', 'Can I learn on my phone?', 'Are certificates included?'].map((x, i) => <details key={x} open={i === 0}><summary>{x}<span>+</span></summary><p>{i === 0 ? 'Yes. There are no long contracts. You can manage or cancel your membership from your student portal at any time.' : 'Yes. The platform is designed to be flexible, accessible and simple to use wherever you are in your studies.'}</p></details>)}</div></section>
    </PageShell>
  )
}

function AuthPage({ register = false }) {
  return (
    <PageShell bare>
      <div className="auth-layout">
        <aside className="auth-art"><Logo light /><div><span className="eyebrow light">Knowledge changes everything</span><h1>{register ? 'Begin with a sincere intention.' : 'Welcome back to your studies.'}</h1><p>Build understanding one clear, considered lesson at a time.</p></div><blockquote>“Whoever travels a path in search of knowledge, Allah will make easy for him a path to Paradise.”<small>— Sahih Muslim</small></blockquote></aside>
        <section className="auth-panel"><Link to="/" className="back-home">← Back to home</Link><div className="auth-form-wrap"><span className="auth-mark"><Sparkles /></span><h2>{register ? 'Create your account' : 'Welcome back'}</h2><p>{register ? 'Join students learning with clarity and purpose.' : 'Continue your learning journey.'}</p>
          {register && <div className="name-row"><label>First name<input placeholder="First name" /></label><label>Last name<input placeholder="Last name" /></label></div>}
          <label>Email address<input type="email" placeholder="you@example.com" /></label>
          <label>Password<div className="password-input"><input type="password" placeholder={register ? 'At least 8 characters' : 'Enter your password'} /><LockKeyhole /></div></label>
          {!register && <div className="form-options"><label><input type="checkbox" /> Remember me</label><a href="#">Forgot password?</a></div>}
          {register && <label className="check-label"><input type="checkbox" /> I agree to the Terms and Privacy Policy.</label>}
          <Link className="button button-primary full" to="/student-portal">{register ? 'Create account' : 'Sign in'} <ArrowRight /></Link>
          <div className="form-divider"><span>or continue with</span></div>
          <button className="button button-outline full">G&nbsp;&nbsp; Continue with Google</button>
          <p className="switch-auth">{register ? 'Already have an account?' : 'New to ASWJ?'} <Link to={register ? '/login' : '/register'}>{register ? 'Sign in' : 'Create an account'}</Link></p>
        </div></section>
      </div>
    </PageShell>
  )
}

function Portal() {
  const [side, setSide] = useState(false)
  const current = courses[2]
  return (
    <PageShell bare>
      <div className="portal">
        <aside className={`portal-sidebar ${side ? 'open' : ''}`}><div className="portal-logo"><Logo light /><button onClick={() => setSide(false)}><X /></button></div><nav><a className="active"><LayoutDashboard />Overview</a><a><Library />My courses</a><a><CalendarDays />Study plan</a><a><MessageCircle />Community</a></nav><div className="sidebar-help"><Sparkles /><b>Need some help?</b><span>Visit the student guide</span><ArrowRight /></div><a className="user-mini"><span>AK</span><div><b>Ahmed Khan</b><small>View profile</small></div><ChevronDown /></a></aside>
        <div className="portal-main"><header className="portal-header"><button className="portal-menu" onClick={() => setSide(true)}><Menu /></button><div><span>Wednesday, 30 September</span></div><div className="portal-actions"><button><Search /></button><button className="notification">2</button><span>AK</span></div></header>
          <main className="dashboard"><div className="welcome"><div><span className="eyebrow">Student portal</span><h1>Assalamu alaikum, Ahmed.</h1><p>Every lesson is a step forward. Here’s where you left off.</p></div><div className="streak"><span>🔥</span><div><b>7 day streak</b><small>Keep it going</small></div></div></div>
            <section className="continue-card"><div className="continue-image"><img src={current.image} alt="" /><button><Play fill="currentColor" /></button></div><div className="continue-copy"><span className="eyebrow">Continue learning</span><h2>The Path to Prayer</h2><p>Lesson 16 of 24 · The pillars of Salah</p><div className="progress-label"><span>64% complete</span><b>16 / 24 lessons</b></div><div className="progress"><i style={{ width: '64%' }} /></div><button className="button button-accent">Resume lesson <ArrowRight /></button></div></section>
            <div className="dashboard-grid"><section><div className="dash-head"><h2>Your courses</h2><a>View all <ArrowRight /></a></div><div className="portal-courses">{courses.slice(1, 3).map(c => <CourseCard portal course={c} key={c.title} />)}</div></section><aside><div className="study-card"><div className="dash-head"><h2>This week</h2><span>3 / 5</span></div><div className="week-bars">{['M','T','W','T','F','S','S'].map((d,i)=><div key={i}><i className={i < 3 ? 'done' : i === 3 ? 'today' : ''} />{d}</div>)}</div><p><Clock3 /> 2h 40m studied</p></div><div className="reflection-card"><span>Daily reflection</span><p>“My Lord, increase me in knowledge.”</p><small>Qur’an 20:114</small></div></aside></div>
          </main>
        </div>
      </div>
    </PageShell>
  )
}

function NotFound() {
  return <PageShell><section className="not-found"><span>404</span><h1>This page hasn’t been written yet.</h1><Link to="/" className="button button-primary">Return home</Link></section></PageShell>
}

export default function App() {
  const location = useLocation()
  useEffect(() => { window.scrollTo(0, 0) }, [location.pathname])
  return <Routes>
    <Route path="/" element={<Home />} />
    <Route path="/home" element={<Home />} />
    <Route path="/courses" element={<Courses />} />
    <Route path="/subscribe" element={<Subscribe />} />
    <Route path="/register" element={<AuthPage register />} />
    <Route path="/login" element={<AuthPage />} />
    <Route path="/student-portal" element={<Portal />} />
    <Route path="*" element={<NotFound />} />
  </Routes>
}
