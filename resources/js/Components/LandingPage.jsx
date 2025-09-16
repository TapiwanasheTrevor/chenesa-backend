import React, { useState } from 'react'
import {
  ArrowRight,
  Droplets,
  Smartphone,
  BarChart3,
  Shield,
  Zap,
  Globe,
  CheckCircle,
  Star,
  Users,
  TrendingUp,
  Menu,
  X,
  Mail,
  Phone,
  MapPin
} from 'lucide-react'

export default function LandingPage() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false)

  return (
    <div className="min-h-screen bg-white">
      {/* Navigation */}
      <nav className="border-b border-gray-200 bg-white/95 backdrop-blur sticky top-0 z-50">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-2">
              <Droplets className="h-8 w-8 text-blue-600" />
              <span className="text-2xl font-bold text-gray-900">Chenesa.io</span>
            </div>

            {/* Desktop Navigation */}
            <div className="hidden md:flex items-center space-x-8">
              <a href="#features" className="text-gray-600 hover:text-gray-900 transition-colors">
                Features
              </a>
              <a href="#solutions" className="text-gray-600 hover:text-gray-900 transition-colors">
                Solutions
              </a>
              <a href="#testimonials" className="text-gray-600 hover:text-gray-900 transition-colors">
                Testimonials
              </a>
              <a href="#contact" className="text-gray-600 hover:text-gray-900 transition-colors">
                Contact
              </a>
              <a href="/admin/login" className="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors inline-flex items-center">
                Admin Login
                <ArrowRight className="ml-2 h-4 w-4" />
              </a>
            </div>

            {/* Mobile menu button */}
            <div className="md:hidden">
              <button
                onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                className="text-gray-600 hover:text-gray-900"
              >
                {mobileMenuOpen ? <X className="h-6 w-6" /> : <Menu className="h-6 w-6" />}
              </button>
            </div>
          </div>
        </div>

        {/* Mobile Navigation */}
        {mobileMenuOpen && (
          <div className="md:hidden border-t border-gray-200 bg-white">
            <div className="px-4 py-2 space-y-2">
              <a href="#features" className="block py-2 text-gray-600 hover:text-gray-900">Features</a>
              <a href="#solutions" className="block py-2 text-gray-600 hover:text-gray-900">Solutions</a>
              <a href="#testimonials" className="block py-2 text-gray-600 hover:text-gray-900">Testimonials</a>
              <a href="#contact" className="block py-2 text-gray-600 hover:text-gray-900">Contact</a>
              <a href="/admin/login" className="block py-2 text-blue-600 font-semibold">Admin Login</a>
            </div>
          </div>
        )}
      </nav>

      {/* Hero Section */}
      <section className="relative py-20 lg:py-32 bg-gradient-to-br from-blue-50 to-cyan-50">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div className="space-y-8">
              <div className="space-y-4">
                <div className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
                  🚀 Revolutionizing Water Management
                </div>
                <h1 className="text-4xl lg:text-6xl font-bold text-gray-900 leading-tight">
                  Real-Time Water Monitoring for
                  <span className="text-blue-600"> Sustainable Future</span>
                </h1>
                <p className="text-xl text-gray-600 leading-relaxed">
                  Empowering businesses across Zimbabwe and South Africa with IoT-powered water management solutions.
                  Monitor, analyze, and optimize your water usage with precision.
                </p>
              </div>
              <div className="flex flex-col sm:flex-row gap-4">
                <a href="/admin/login" className="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg font-semibold transition-colors inline-flex items-center justify-center">
                  Start Free Trial
                  <ArrowRight className="ml-2 h-5 w-5" />
                </a>
                <button className="border-2 border-blue-600 text-blue-600 hover:bg-blue-50 px-8 py-4 rounded-lg font-semibold transition-colors">
                  Watch Demo
                </button>
              </div>
              <div className="flex items-center gap-8 pt-4">
                <div className="text-center">
                  <div className="text-2xl font-bold text-blue-600">500+</div>
                  <div className="text-sm text-gray-600">Active Sensors</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-blue-600">99.9%</div>
                  <div className="text-sm text-gray-600">Uptime</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-blue-600">24/7</div>
                  <div className="text-sm text-gray-600">Monitoring</div>
                </div>
              </div>
            </div>
            <div className="relative">
              <div className="relative bg-white rounded-2xl p-8 shadow-2xl border border-gray-200">
                <img
                  src="/iot-water-monitoring-dashboard-with-real-time-data.jpg"
                  alt="Chenesa.io Dashboard"
                  className="rounded-lg w-full"
                />
                <div className="absolute -top-4 -right-4 bg-green-500 text-white px-4 py-2 rounded-full text-sm font-semibold shadow-lg">
                  Live Data
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-20 bg-white">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center space-y-4 mb-16">
            <div className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
              Advanced Features
            </div>
            <h2 className="text-3xl lg:text-5xl font-bold text-gray-900">
              Everything You Need for Smart Water Management
            </h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              Our comprehensive IoT platform provides real-time insights, predictive analytics, and seamless integration
              across all your water systems.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <FeatureCard
              icon={<BarChart3 className="h-6 w-6 text-blue-600" />}
              title="Real-Time Analytics"
              description="Monitor water levels, consumption patterns, and system performance with sub-second precision across all your tanks."
            />
            <FeatureCard
              icon={<Smartphone className="h-6 w-6 text-green-600" />}
              title="Mobile App"
              description="iOS and Android apps with push notifications, offline capability, and intuitive controls for managing your water systems on the go."
            />
            <FeatureCard
              icon={<Shield className="h-6 w-6 text-blue-600" />}
              title="Enterprise Security"
              description="Bank-grade encryption, role-based access control, and comprehensive audit logging to keep your data secure."
            />
            <FeatureCard
              icon={<Zap className="h-6 w-6 text-green-600" />}
              title="Predictive Alerts"
              description="AI-powered predictions for refill schedules, maintenance needs, and potential issues before they become problems."
            />
            <FeatureCard
              icon={<Globe className="h-6 w-6 text-blue-600" />}
              title="Multi-Location Support"
              description="Manage water systems across multiple sites in Zimbabwe and South Africa from a single, unified dashboard."
            />
            <FeatureCard
              icon={<TrendingUp className="h-6 w-6 text-green-600" />}
              title="Cost Optimization"
              description="Reduce water waste by up to 40% with intelligent usage patterns, leak detection, and automated ordering systems."
            />
          </div>
        </div>
      </section>

      {/* Solutions Section */}
      <section id="solutions" className="py-20 bg-gray-50">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center space-y-4 mb-16">
            <div className="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-3 py-1 text-sm font-medium text-green-700">
              Industry Solutions
            </div>
            <h2 className="text-3xl lg:text-5xl font-bold text-gray-900">Tailored for Your Industry</h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              From agriculture to manufacturing, our IoT water management solutions adapt to your specific needs and challenges.
            </p>
          </div>

          <div className="grid lg:grid-cols-3 gap-8">
            <SolutionCard
              image="/agricultural-irrigation-system-with-smart-sensors.jpg"
              title="Agriculture & Irrigation"
              description="Optimize crop yields with precision irrigation monitoring, soil moisture tracking, and automated water distribution systems."
              features={["Soil moisture sensors", "Weather integration", "Automated irrigation"]}
            />
            <SolutionCard
              image="/industrial-water-treatment-facility-with-monitorin.jpg"
              title="Industrial & Manufacturing"
              description="Ensure continuous operations with real-time monitoring of cooling systems, process water, and waste water treatment."
              features={["Process monitoring", "Quality control", "Compliance reporting"]}
            />
            <SolutionCard
              image="/municipal-water-distribution-system-with-smart-met.jpg"
              title="Municipal & Utilities"
              description="Manage city-wide water distribution, detect leaks early, and optimize resource allocation for growing populations."
              features={["Smart meters", "Leak detection", "Usage analytics"]}
            />
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section id="testimonials" className="py-20 bg-white">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center space-y-4 mb-16">
            <div className="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700">
              Customer Success
            </div>
            <h2 className="text-3xl lg:text-5xl font-bold text-gray-900">Trusted by Industry Leaders</h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              See how organizations across Zimbabwe and South Africa are transforming their water management with Chenesa.io.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <TestimonialCard
              rating={5}
              quote="Chenesa.io has revolutionized our irrigation management. We've reduced water waste by 35% while increasing crop yields. The real-time alerts have prevented multiple costly equipment failures."
              author="Sarah Mukamuri"
              position="Farm Manager, Zimbabwe"
            />
            <TestimonialCard
              rating={5}
              quote="The predictive analytics have been game-changing for our manufacturing operations. We now anticipate maintenance needs weeks in advance, eliminating unexpected downtime."
              author="Michael van der Merwe"
              position="Operations Director, South Africa"
            />
            <TestimonialCard
              rating={5}
              quote="Implementation was seamless, and the support team is exceptional. Our water distribution efficiency has improved by 40% across all our municipal systems."
              author="Thandiwe Moyo"
              position="City Engineer, Zimbabwe"
            />
          </div>
        </div>
      </section>

      {/* Contact Section */}
      <section id="contact" className="py-20 bg-gray-50">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center space-y-4 mb-16">
            <div className="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-3 py-1 text-sm font-medium text-green-700">
              Get In Touch
            </div>
            <h2 className="text-3xl lg:text-5xl font-bold text-gray-900">Contact Our Team</h2>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              Ready to transform your water management? Get in touch with our experts today.
            </p>
          </div>

          <div className="grid lg:grid-cols-2 gap-12">
            <div className="space-y-8">
              <div className="space-y-6">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <Mail className="h-6 w-6 text-blue-600" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900">Email Us</h3>
                    <p className="text-gray-600">info@chenesa.io</p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <Phone className="h-6 w-6 text-green-600" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900">Call Us</h3>
                    <p className="text-gray-600">+263 4 123 4567 (Zimbabwe)</p>
                    <p className="text-gray-600">+27 11 123 4567 (South Africa)</p>
                  </div>
                </div>
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <MapPin className="h-6 w-6 text-blue-600" />
                  </div>
                  <div>
                    <h3 className="font-semibold text-gray-900">Visit Us</h3>
                    <p className="text-gray-600">Harare, Zimbabwe</p>
                    <p className="text-gray-600">Cape Town, South Africa</p>
                  </div>
                </div>
              </div>
            </div>

            <div className="bg-white rounded-lg p-8 shadow-lg">
              <form className="space-y-6">
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input
                      type="text"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="John"
                    />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                    <input
                      type="text"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      placeholder="Doe"
                    />
                  </div>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Email</label>
                  <input
                    type="email"
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="john@example.com"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Company</label>
                  <input
                    type="text"
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Your Company"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-2">Message</label>
                  <textarea
                    rows={4}
                    className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Tell us about your water management needs..."
                  />
                </div>
                <button
                  type="submit"
                  className="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors"
                >
                  Send Message
                </button>
              </form>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-blue-600">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="max-w-3xl mx-auto space-y-8">
            <h2 className="text-3xl lg:text-5xl font-bold text-white">Ready to Transform Your Water Management?</h2>
            <p className="text-xl text-blue-100">
              Join hundreds of organizations already saving water, reducing costs, and improving efficiency with
              Chenesa.io's IoT solutions.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <a href="/admin/login" className="bg-white text-blue-600 hover:bg-gray-50 px-8 py-4 rounded-lg font-semibold transition-colors inline-flex items-center justify-center">
                Start Free Trial
                <ArrowRight className="ml-2 h-5 w-5" />
              </a>
              <button className="border-2 border-white text-white hover:bg-white/10 px-8 py-4 rounded-lg font-semibold transition-colors">
                Schedule Demo
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-16">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid md:grid-cols-4 gap-8">
            <div className="space-y-4">
              <div className="flex items-center space-x-2">
                <Droplets className="h-6 w-6 text-blue-400" />
                <span className="text-xl font-bold">Chenesa.io</span>
              </div>
              <p className="text-gray-400">
                Revolutionizing water management across Zimbabwe and South Africa with cutting-edge IoT technology.
              </p>
            </div>
            <div>
              <h3 className="font-semibold mb-4">Solutions</h3>
              <ul className="space-y-2 text-gray-400">
                <li><a href="#" className="hover:text-white transition-colors">Agriculture</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Industrial</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Municipal</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Residential</a></li>
              </ul>
            </div>
            <div>
              <h3 className="font-semibold mb-4">Company</h3>
              <ul className="space-y-2 text-gray-400">
                <li><a href="#" className="hover:text-white transition-colors">About Us</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Careers</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Blog</a></li>
                <li><a href="#contact" className="hover:text-white transition-colors">Contact</a></li>
              </ul>
            </div>
            <div>
              <h3 className="font-semibold mb-4">Support</h3>
              <ul className="space-y-2 text-gray-400">
                <li><a href="#" className="hover:text-white transition-colors">Documentation</a></li>
                <li><a href="#" className="hover:text-white transition-colors">API Reference</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Help Center</a></li>
                <li><a href="#" className="hover:text-white transition-colors">Status</a></li>
              </ul>
            </div>
          </div>
          <div className="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
            <p>&copy; 2025 Chenesa.io. All rights reserved. Empowering sustainable water management across Africa.</p>
          </div>
        </div>
      </footer>
    </div>
  )
}

function FeatureCard({ icon, title, description }) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
      <div className="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center mb-4">
        {icon}
      </div>
      <h3 className="text-xl font-semibold text-gray-900 mb-2">{title}</h3>
      <p className="text-gray-600">{description}</p>
    </div>
  )
}

function SolutionCard({ image, title, description, features }) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 hover:shadow-xl transition-all duration-300">
      <div className="p-6">
        <img
          src={image}
          alt={title}
          className="rounded-lg mb-4 w-full h-48 object-cover"
        />
        <h3 className="text-xl font-semibold text-gray-900 mb-2">{title}</h3>
        <p className="text-gray-600 mb-6">{description}</p>
        <ul className="space-y-2">
          {features.map((feature, index) => (
            <li key={index} className="flex items-center gap-2">
              <CheckCircle className="h-4 w-4 text-blue-600" />
              <span className="text-sm text-gray-600">{feature}</span>
            </li>
          ))}
        </ul>
      </div>
    </div>
  )
}

function TestimonialCard({ rating, quote, author, position }) {
  return (
    <div className="bg-white rounded-lg border border-gray-200 p-6">
      <div className="flex items-center gap-1 mb-4">
        {[...Array(rating)].map((_, i) => (
          <Star key={i} className="h-4 w-4 fill-yellow-400 text-yellow-400" />
        ))}
      </div>
      <p className="text-gray-600 italic mb-6">"{quote}"</p>
      <div className="flex items-center gap-3">
        <div className="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
          <Users className="h-5 w-5 text-blue-600" />
        </div>
        <div>
          <div className="font-semibold text-gray-900">{author}</div>
          <div className="text-sm text-gray-600">{position}</div>
        </div>
      </div>
    </div>
  )
}