import { useState } from 'react'
import { Routes, Route, useLocation } from 'react-router-dom'
import Sidebar from './components/sidebar/sidebar'
import Dashboard from './pages/Dashboard'
import GeneralSettings from './pages/settings/General'
import SecuritySettings from './pages/settings/Security'
import Header from './components/header/Header'
import sidebarItems from './components/sidebar/sidebarItems'


import RPTConfig from './pages/RPT/RPTConfig/RPTConfig'

import RPTAssess from './pages/RPT/RPTAssess/RPTAssess'
import RPTDetails from './pages/RPT/RPTAssess/RPTDetails'


import BusinessAssess from './pages/Business/BusinessAssess/BusinessAssess'
import BusinessView from './pages/Business/BusinessAssess/BusinessView'

import BusinessStatus from './pages/Business/BusinessStatus/BusinessStatus'
import BusinessStatusView from './pages/Business/BusinessStatus/BusinessStatusView'

import Reveneu from './pages/Treasury/Revenue'
import Disbursement from './pages/Treasury/Disbursement'
import Report from './pages/Treasury/Report'

import Transact from './pages/Digital/Transact'


import MarketCreator from './pages/Market/MapCreator/MapCreator'
import MarketOutput from './pages/Market/MapCreator/MarketOutput'
import ViewAllMaps from './pages/Market/MapCreator/ViewAllMaps'
import MapEditor from './pages/Market/MapCreator/MapEditor'

import RentApproval from './pages/Market/RentApproval/RentApproval'
import RenterDetails from './pages/Market/RentApproval/RenterDetails'

import RenterRent from './pages/Market/RenterRent/RenterRent'
import RenterStatus from './pages/Market/RenterRent/RenterStatus'




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
      <div className='flex h-screen overflow-hidden'>
        <Sidebar collapsed={sidebarCollapsed} />
        <div className='flex-1 flex flex-col'>
          <Header
            sidebarCollapsed={sidebarCollapsed}
            onToggleSidebar={() => setSidebarCollapsed(!sidebarCollapsed)}
            breadcrumb={getBreadcrumb()}
          />
          <main className="flex-1 overflow-auto p-8 dark:bg-slate-800">
            <Routes>
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/RPT/RPTConfig" element={<RPTConfig />} />
              <Route path="/RPT/RPTAssess" element={<RPTAssess />} />
              <Route path="/RPT/RPTDetails/:id" element={<RPTDetails />} />
             

              <Route path="/Business/BusinessAssess" element={<BusinessAssess />} />
              <Route path="/Business/BusinessView/:id" element={<BusinessView />} />
              <Route path="/Business/BusinessStatus" element={<BusinessStatus />} />
              <Route path="/Business/BusinessStatusView/:id" element={<BusinessStatusView />} />

              <Route path="/Treasury/Revenue" element={<Reveneu />} />
              <Route path="/Treasury/Disbursement" element={<Disbursement />} />
              <Route path="/Treasury/Report" element={<Report />} />

              <Route path="/Digital/Transact" element={<Transact />} />
   
              <Route path="/Market/MarketCreator" element={<MarketCreator />} />
              <Route path="/Market/MarketOutput/view/:id" element={<MarketOutput />} />
              <Route path="/Market/ViewAllMaps" element={<ViewAllMaps />} />
              <Route path="/Market/MapEditor/:id" element={<MapEditor />} />

              <Route path="/Market/RentApproval" element={<RentApproval />} />
              <Route path="/Market/RenterDetails/:id" element={<RenterDetails />} />

              <Route path="/Market/RenterRent" element={<RenterRent />} />
              <Route path="/Market/RenterStatus/:id" element={<RenterStatus />} />


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