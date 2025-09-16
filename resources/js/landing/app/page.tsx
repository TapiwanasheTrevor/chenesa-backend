import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
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
} from "lucide-react"
import Image from "next/image"

export default function HomePage() {
  return (
    <div className="min-h-screen bg-background">
      {/* Navigation */}
      <nav className="border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60 sticky top-0 z-50">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <div className="flex items-center space-x-2">
              <Droplets className="h-8 w-8 text-primary" />
              <span className="text-2xl font-bold text-foreground">Chenesa.io</span>
            </div>
            <div className="hidden md:flex items-center space-x-8">
              <a href="#features" className="text-muted-foreground hover:text-foreground transition-colors">
                Features
              </a>
              <a href="#solutions" className="text-muted-foreground hover:text-foreground transition-colors">
                Solutions
              </a>
              <a href="#testimonials" className="text-muted-foreground hover:text-foreground transition-colors">
                Testimonials
              </a>
              <a href="#contact" className="text-muted-foreground hover:text-foreground transition-colors">
                Contact
              </a>
              <Button className="bg-primary hover:bg-primary/90 text-primary-foreground">
                Get Started
                <ArrowRight className="ml-2 h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="relative py-20 lg:py-32 bg-gradient-to-br from-card to-background">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div className="space-y-8">
              <div className="space-y-4">
                <Badge variant="secondary" className="bg-accent/10 text-accent-foreground border-accent/20">
                  🚀 Revolutionizing Water Management
                </Badge>
                <h1 className="text-4xl lg:text-6xl font-bold text-foreground leading-tight text-balance">
                  Real-Time Water Monitoring for
                  <span className="text-primary"> Sustainable Future</span>
                </h1>
                <p className="text-xl text-muted-foreground leading-relaxed text-pretty">
                  Empowering businesses across Zimbabwe and South Africa with IoT-powered water management solutions.
                  Monitor, analyze, and optimize your water usage with precision.
                </p>
              </div>
              <div className="flex flex-col sm:flex-row gap-4">
                <Button size="lg" className="bg-primary hover:bg-primary/90 text-primary-foreground">
                  Start Free Trial
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Button>
                <Button
                  size="lg"
                  variant="outline"
                  className="border-primary text-primary hover:bg-primary/5 bg-transparent"
                >
                  Watch Demo
                </Button>
              </div>
              <div className="flex items-center gap-8 pt-4">
                <div className="text-center">
                  <div className="text-2xl font-bold text-primary">500+</div>
                  <div className="text-sm text-muted-foreground">Active Sensors</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-primary">99.9%</div>
                  <div className="text-sm text-muted-foreground">Uptime</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-primary">24/7</div>
                  <div className="text-sm text-muted-foreground">Monitoring</div>
                </div>
              </div>
            </div>
            <div className="relative">
              <div className="relative bg-card rounded-2xl p-8 shadow-2xl border border-border">
                <Image
                  src="/iot-water-monitoring-dashboard-with-real-time-data.jpg"
                  alt="Chenesa.io Dashboard"
                  width={600}
                  height={400}
                  className="rounded-lg"
                />
                <div className="absolute -top-4 -right-4 bg-accent text-accent-foreground px-4 py-2 rounded-full text-sm font-semibold shadow-lg">
                  Live Data
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="py-20 bg-background">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center space-y-4 mb-16">
            <Badge variant="secondary" className="bg-primary/10 text-primary border-primary/20">
              Advanced Features
            </Badge>
            <h2 className="text-3xl lg:text-5xl font-bold text-foreground text-balance">
              Everything You Need for Smart Water Management
            </h2>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto text-pretty">
              Our comprehensive IoT platform provides real-time insights, predictive analytics, and seamless integration
              across all your water systems.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                  <BarChart3 className="h-6 w-6 text-primary" />
                </div>
                <CardTitle className="text-foreground">Real-Time Analytics</CardTitle>
                <CardDescription className="text-muted-foreground">
                  Monitor water levels, consumption patterns, and system performance with sub-second precision across
                  all your tanks.
                </CardDescription>
              </CardHeader>
            </Card>

            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center mb-4">
                  <Smartphone className="h-6 w-6 text-accent" />
                </div>
                <CardTitle className="text-foreground">Mobile App</CardTitle>
                <CardDescription className="text-muted-foreground">
                  iOS and Android apps with push notifications, offline capability, and intuitive controls for managing
                  your water systems on the go.
                </CardDescription>
              </CardHeader>
            </Card>

            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                  <Shield className="h-6 w-6 text-primary" />
                </div>
                <CardTitle className="text-foreground">Enterprise Security</CardTitle>
                <CardDescription className="text-muted-foreground">
                  Bank-grade encryption, role-based access control, and comprehensive audit logging to keep your data
                  secure.
                </CardDescription>
              </CardHeader>
            </Card>

            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center mb-4">
                  <Zap className="h-6 w-6 text-accent" />
                </div>
                <CardTitle className="text-foreground">Predictive Alerts</CardTitle>
                <CardDescription className="text-muted-foreground">
                  AI-powered predictions for refill schedules, maintenance needs, and potential issues before they
                  become problems.
                </CardDescription>
              </CardHeader>
            </Card>

            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                  <Globe className="h-6 w-6 text-primary" />
                </div>
                <CardTitle className="text-foreground">Multi-Location Support</CardTitle>
                <CardDescription className="text-muted-foreground">
                  Manage water systems across multiple sites in Zimbabwe and South Africa from a single, unified
                  dashboard.
                </CardDescription>
              </CardHeader>
            </Card>

            <Card className="border-border hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center mb-4">
                  <TrendingUp className="h-6 w-6 text-accent" />
                </div>
                <CardTitle className="text-foreground">Cost Optimization</CardTitle>
                <CardDescription className="text-muted-foreground">
                  Reduce water waste by up to 40% with intelligent usage patterns, leak detection, and automated
                  ordering systems.
                </CardDescription>
              </CardHeader>
            </Card>
          </div>
        </div>
      </section>

      {/* Solutions Section */}
      <section id="solutions" className="py-20 bg-card">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center space-y-4 mb-16">
            <Badge variant="secondary" className="bg-accent/10 text-accent-foreground border-accent/20">
              Industry Solutions
            </Badge>
            <h2 className="text-3xl lg:text-5xl font-bold text-foreground text-balance">Tailored for Your Industry</h2>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto text-pretty">
              From agriculture to manufacturing, our IoT water management solutions adapt to your specific needs and
              challenges.
            </p>
          </div>

          <div className="grid lg:grid-cols-3 gap-8">
            <Card className="bg-background border-border hover:shadow-xl transition-all duration-300">
              <CardHeader>
                <Image
                  src="/agricultural-irrigation-system-with-smart-sensors.jpg"
                  alt="Agriculture Solution"
                  width={400}
                  height={200}
                  className="rounded-lg mb-4"
                />
                <CardTitle className="text-foreground">Agriculture & Irrigation</CardTitle>
                <CardDescription className="text-muted-foreground">
                  Optimize crop yields with precision irrigation monitoring, soil moisture tracking, and automated water
                  distribution systems.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Soil moisture sensors</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Weather integration</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Automated irrigation</span>
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Card className="bg-background border-border hover:shadow-xl transition-all duration-300">
              <CardHeader>
                <Image
                  src="/industrial-water-treatment-facility-with-monitorin.jpg"
                  alt="Industrial Solution"
                  width={400}
                  height={200}
                  className="rounded-lg mb-4"
                />
                <CardTitle className="text-foreground">Industrial & Manufacturing</CardTitle>
                <CardDescription className="text-muted-foreground">
                  Ensure continuous operations with real-time monitoring of cooling systems, process water, and waste
                  water treatment.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Process monitoring</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Quality control</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Compliance reporting</span>
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Card className="bg-background border-border hover:shadow-xl transition-all duration-300">
              <CardHeader>
                <Image
                  src="/municipal-water-distribution-system-with-smart-met.jpg"
                  alt="Municipal Solution"
                  width={400}
                  height={200}
                  className="rounded-lg mb-4"
                />
                <CardTitle className="text-foreground">Municipal & Utilities</CardTitle>
                <CardDescription className="text-muted-foreground">
                  Manage city-wide water distribution, detect leaks early, and optimize resource allocation for growing
                  populations.
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Smart meters</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Leak detection</span>
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    <span className="text-sm text-muted-foreground">Usage analytics</span>
                  </li>
                </ul>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* Testimonials Section */}
      <section id="testimonials" className="py-20 bg-background">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center space-y-4 mb-16">
            <Badge variant="secondary" className="bg-primary/10 text-primary border-primary/20">
              Customer Success
            </Badge>
            <h2 className="text-3xl lg:text-5xl font-bold text-foreground text-balance">Trusted by Industry Leaders</h2>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto text-pretty">
              See how organizations across Zimbabwe and South Africa are transforming their water management with
              Chenesa.io.
            </p>
          </div>

          <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            <Card className="border-border">
              <CardHeader>
                <div className="flex items-center gap-1 mb-4">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} className="h-4 w-4 fill-accent text-accent" />
                  ))}
                </div>
                <CardDescription className="text-muted-foreground italic">
                  "Chenesa.io has revolutionized our irrigation management. We've reduced water waste by 35% while
                  increasing crop yields. The real-time alerts have prevented multiple costly equipment failures."
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                    <Users className="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <div className="font-semibold text-foreground">Sarah Mukamuri</div>
                    <div className="text-sm text-muted-foreground">Farm Manager, Zimbabwe</div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="border-border">
              <CardHeader>
                <div className="flex items-center gap-1 mb-4">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} className="h-4 w-4 fill-accent text-accent" />
                  ))}
                </div>
                <CardDescription className="text-muted-foreground italic">
                  "The predictive analytics have been game-changing for our manufacturing operations. We now anticipate
                  maintenance needs weeks in advance, eliminating unexpected downtime."
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-accent/10 rounded-full flex items-center justify-center">
                    <Users className="h-5 w-5 text-accent" />
                  </div>
                  <div>
                    <div className="font-semibold text-foreground">Michael van der Merwe</div>
                    <div className="text-sm text-muted-foreground">Operations Director, South Africa</div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="border-border">
              <CardHeader>
                <div className="flex items-center gap-1 mb-4">
                  {[...Array(5)].map((_, i) => (
                    <Star key={i} className="h-4 w-4 fill-accent text-accent" />
                  ))}
                </div>
                <CardDescription className="text-muted-foreground italic">
                  "Implementation was seamless, and the support team is exceptional. Our water distribution efficiency
                  has improved by 40% across all our municipal systems."
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-3">
                  <div className="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                    <Users className="h-5 w-5 text-primary" />
                  </div>
                  <div>
                    <div className="font-semibold text-foreground">Thandiwe Moyo</div>
                    <div className="text-sm text-muted-foreground">City Engineer, Zimbabwe</div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-20 bg-primary text-primary-foreground">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="max-w-3xl mx-auto space-y-8">
            <h2 className="text-3xl lg:text-5xl font-bold text-balance">Ready to Transform Your Water Management?</h2>
            <p className="text-xl opacity-90 text-pretty">
              Join hundreds of organizations already saving water, reducing costs, and improving efficiency with
              Chenesa.io's IoT solutions.
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Button size="lg" variant="secondary" className="bg-background text-foreground hover:bg-background/90">
                Start Free Trial
                <ArrowRight className="ml-2 h-5 w-5" />
              </Button>
              <Button
                size="lg"
                variant="outline"
                className="border-primary-foreground text-primary-foreground hover:bg-primary-foreground/10 bg-transparent"
              >
                Schedule Demo
              </Button>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-card border-t border-border">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="grid md:grid-cols-4 gap-8">
            <div className="space-y-4">
              <div className="flex items-center space-x-2">
                <Droplets className="h-6 w-6 text-primary" />
                <span className="text-xl font-bold text-foreground">Chenesa.io</span>
              </div>
              <p className="text-muted-foreground">
                Revolutionizing water management across Zimbabwe and South Africa with cutting-edge IoT technology.
              </p>
            </div>
            <div>
              <h3 className="font-semibold text-foreground mb-4">Solutions</h3>
              <ul className="space-y-2 text-muted-foreground">
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Agriculture
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Industrial
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Municipal
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Residential
                  </a>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="font-semibold text-foreground mb-4">Company</h3>
              <ul className="space-y-2 text-muted-foreground">
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    About Us
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Careers
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Blog
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Contact
                  </a>
                </li>
              </ul>
            </div>
            <div>
              <h3 className="font-semibold text-foreground mb-4">Support</h3>
              <ul className="space-y-2 text-muted-foreground">
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Documentation
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    API Reference
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Help Center
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-foreground transition-colors">
                    Status
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <div className="border-t border-border mt-12 pt-8 text-center text-muted-foreground">
            <p>&copy; 2024 Chenesa.io. All rights reserved. Empowering sustainable water management across Africa.</p>
          </div>
        </div>
      </footer>
    </div>
  )
}
