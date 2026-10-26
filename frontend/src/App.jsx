import React, { useState, useEffect, useMemo } from 'react';
import Navbar from './components/Navbar.jsx';
import HeroFilters from './components/HeroFilters.jsx';
import FeaturedPropsGrid from './components/FeaturedPropsGrid.jsx';
import PropsTableList from './components/PropsTableList.jsx';
import StatsDrawer from './components/StatsDrawer.jsx';
import TacticalHeatmapModal from './components/TacticalHeatmapModal.jsx';
import { getProps } from './services/propsService.js';
import { Loader2, RefreshCw } from 'lucide-react';

export default function App() {
  const [selectedSport, setSelectedSport] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [propsData, setPropsData] = useState([]);
  const [dataSource, setDataSource] = useState('mock');
  const [loading, setLoading] = useState(true);
  const [selectedProp, setSelectedProp] = useState(null);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);
  const [isHeatmapOpen, setIsHeatmapOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('dashboard');

  // Load props from asynchronous service (Fetch API)
  useEffect(() => {
    let isMounted = true;
    async function loadData() {
      setLoading(true);
      try {
        const res = await getProps(selectedSport);
        if (isMounted) {
          setPropsData(res.props);
          setDataSource(res.source);
        }
      } catch (err) {
        console.error('Error fetching props:', err);
      } finally {
        if (isMounted) setLoading(false);
      }
    }

    loadData();
    return () => {
      isMounted = false;
    };
  }, [selectedSport]);

  // Client-side real-time filter by search query
  const filteredProps = useMemo(() => {
    if (!searchQuery.trim()) return propsData;
    const q = searchQuery.toLowerCase().trim();
    return propsData.filter((p) => {
      return (
        p.player_name.toLowerCase().includes(q) ||
        p.team_name.toLowerCase().includes(q) ||
        p.team_abbreviation.toLowerCase().includes(q) ||
        p.bet_type_display.toLowerCase().includes(q) ||
        p.player_position.toLowerCase().includes(q)
      );
    });
  }, [propsData, searchQuery]);

  const handleOpenStats = (prop) => {
    setSelectedProp(prop);
    setIsDrawerOpen(true);
  };

  const handleCloseStats = () => {
    setIsDrawerOpen(false);
  };

  return (
    <div className="min-h-screen bg-[#050811] text-slate-100 flex flex-col justify-between selection:bg-brand-cyan/20 selection:text-brand-cyan">
      
      {/* Top Navigation */}
      <div>
        <Navbar
          activeTab={activeTab}
          onSelectTab={setActiveTab}
          onOpenHeatmap={() => setIsHeatmapOpen(true)}
        />

        {/* Hero & Real-time Filter Bar */}
        <HeroFilters
          selectedSport={selectedSport}
          onSelectSport={setSelectedSport}
          searchQuery={searchQuery}
          onSearchChange={setSearchQuery}
          totalPropsCount={filteredProps.length}
          featuredCount={filteredProps.filter((p) => p.is_featured).length}
        />

        {/* Loading Indicator */}
        {loading ? (
          <div className="flex flex-col items-center justify-center py-20 text-slate-400">
            <Loader2 className="h-8 w-8 animate-spin text-brand-cyan mb-2" />
            <p className="text-xs font-mono">Consultando cuotas en vivo...</p>
          </div>
        ) : (
          <main>
            {/* Level 1: Featured Picks Grid (Only on Dashboard tab or when searching) */}
            {(activeTab === 'dashboard' || searchQuery) && (
              <FeaturedPropsGrid
                props={filteredProps}
                onOpenStats={handleOpenStats}
              />
            )}

            {/* Level 2: Full Propositions Catalog with USA Sportsbooks */}
            <PropsTableList
              props={filteredProps}
              onOpenStats={handleOpenStats}
              searchQuery={searchQuery}
            />
          </main>
        )}
      </div>

      {/* Slide-over Stats & Odds Drawer */}
      <StatsDrawer
        prop={selectedProp}
        isOpen={isDrawerOpen}
        onClose={handleCloseStats}
      />

      {/* Tactical NFL Field Heatmap Modal */}
      <TacticalHeatmapModal
        isOpen={isHeatmapOpen}
        onClose={() => setIsHeatmapOpen(false)}
      />

      {/* Modern Minimal Footer */}
      <footer className="mt-16 border-t border-slate-800/80 bg-[#070B16] py-8 text-center text-xs text-slate-400">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div className="flex items-center gap-2">
            <img src="/logo_matchupiq_app.png" alt="MatchupIQ" className="h-6 w-auto opacity-75" />
            <span className="font-display font-bold text-white tracking-wide">MatchupIQ</span>
            <span className="text-[11px] text-slate-400">— Sprint 2 Frontend Delivery</span>
          </div>

          <div className="text-[11px] font-mono text-slate-400">
            Lead Frontend & UI/UX: <strong className="text-slate-300">Carlos Antonio Villaseñor García</strong> (ID: 441641)
          </div>

          <div className="text-[11px] text-slate-400">
            ISC 5-A • Programación de Sistemas Web
          </div>
        </div>
      </footer>

    </div>
  );
}
