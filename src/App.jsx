// src/App.jsx
import { useState } from 'react'
import { Routes, Route, useLocation } from 'react-router-dom'

// Components
import Sidebar from './components/sidebar/sidebar'
import Header from './components/header/Header'
import sidebarItems from './components/sidebar/sidebarItems'

// Pages
import Dashboard from './pages/Dashboard'
import GeneralSettings from './pages/settings/General'
import SecuritySettings from './pages/settings/Security'

// RPT
import RPT1 from './pages/RPT/RPT1'
import RPT2 from './pages/RPT/RPT2'

// Business
import Business1 from './pages/Business/Business1'
import Business2 from './pages/Business/Business2'

// Treasury
import Treasury1 from './pages/Treasury/Treasury1'
import Treasury2 from './pages/Treasury/Treasury2'
import Treasury3 from './pages/Treasury/Treasury3'

// Digital
import Digital1 from './pages/Digital/Digital1'
import Digital2 from './pages/Digital/Digital2'
import Digital3 from './pages/Digital/Digital3'

// Market
import Market1 from './pages/Market/Market1/Market1'
import Market2 from './pages/Market/Market2'
import MarketView from './pages/Market/Market1/MarketView'
import MarketEdit from './pages/Market/Market1/MarketEdit'

function App() {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false)
  const location = useLocation()

  // Helper to find breadcrumb path from sidebarItems
  function getBreadcrumb() {
    for (const item of sidebarItems) {
      if (item.path === location.pathname) return [item.label]
      if (item.subItems) {
        const sub = item.subItems.find(sub => sub.path === location.pathname)
        if (sub) return [item.label, sub.label]
      }
    }
    return ['Dashboard']
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-800 dark:via-slate-800 dark:to-slate-800 transition-colors duration-200">
      <div className="flex h-screen overflow-hidden">
        <Sidebar collapsed={sidebarCollapsed} />
        <div className="flex-1 flex flex-col">
          <Header
            sidebarCollapsed={sidebarCollapsed}
            onToggleSidebar={() => setSidebarCollapsed(!sidebarCollapsed)}
            breadcrumb={getBreadcrumb()}
          />
          <main className="flex-1 overflow-auto p-8 dark:bg-slate-800">
            <Routes>
              {/* Dashboard */}
              <Route path="/dashboard" element={<Dashboard />} />

              {/* RPT */}
              <Route path="/RPT/RPT1" element={<RPT1 />} />
              <Route path="/RPT/RPT2" element={<RPT2 />} />

              {/* Business */}
              <Route path="/Business/Business1" element={<Business1 />} />
              <Route path="/Business/Business2" element={<Business2 />} />

              {/* Treasury */}
              <Route path="/Treasury/Treasury1" element={<Treasury1 />} />
              <Route path="/Treasury/Treasury2" element={<Treasury2 />} />
              <Route path="/Treasury/Treasury3" element={<Treasury3 />} />

              {/* Digital */}
              <Route path="/Digital/Digital1" element={<Digital1 />} />
              <Route path="/Digital/Digital2" element={<Digital2 />} />
              <Route path="/Digital/Digital3" element={<Digital3 />} />

              {/* Market */}
              <Route path="/Market/Market1/Market1" element={<Market1 />} />
              <Route path="/Market/Market2" element={<Market2 />} />
              <Route path="/Market/view/:id" element={<MarketView />} />
              <Route path="/Market/edit" element={<MarketEdit />} />
              <Route path="/Market/edit/:id" element={<MarketEdit />} />

              {/* Settings */}
              <Route path="/settings/general" element={<GeneralSettings />} />
              <Route path="/settings/security" element={<SecuritySettings />} />
            </Routes>
          </main>
        </div>
      </div>
    </div>
  )
}

export default App
