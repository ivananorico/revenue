import Dashboard from './pages/Dashboard'
import RPT1 from './pages/RPT/RPT1'
import Business1 from './pages/Business/Business1'
import Treasury1 from './pages/Treasury/Treasury1'
import Digital1 from './pages/Digital/Digital1'

// Market1
import Market1 from './pages/Market/Market1/Market1'
import MapOutput from './pages/Market/Market1/MapOutput'
import MarketEdit from './pages/Market/Market1/MarketEdit'

// Market2
import Market2 from './pages/Market/Market2/Market2'
import RenterDetails from './pages/Market/Market2/RenterDetails'

import GeneralSettings from './pages/settings/General'
import SecuritySettings from './pages/settings/Security'

const routes = [
  { path: '/dashboard', element: <Dashboard /> },

  // RPT
  { path: '/rpt/rpt1', element: <RPT1 /> },

  // Business
  { path: '/business/business1', element: <Business1 /> },

  // Treasury
  { path: '/treasury/treasury1', element: <Treasury1 /> },

  // Digital
  { path: '/digital/digital1', element: <Digital1 /> },

  // Market1
  { path: '/market1/market1', element: <Market1 /> },
  { path: '/market1/view/:id', element: <MapOutput /> },
  { path: '/market1/edit/:id', element: <MarketEdit /> },

  // Market2
  { path: '/market2/market2', element: <Market2 /> },
  { path: '/market2/renter/:id', element: <RenterDetails /> },

  // Settings
  { path: '/settings/general', element: <GeneralSettings /> },
  { path: '/settings/security', element: <SecuritySettings /> },
]

export default routes
