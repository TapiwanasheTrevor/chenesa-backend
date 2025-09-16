import React from 'react'
import '../css/app.css'
import { createRoot } from 'react-dom/client'
import LandingPage from './Components/LandingPage'

const root = createRoot(document.getElementById('landing-app'))
root.render(<LandingPage />)