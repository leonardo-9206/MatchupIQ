import { test, expect, propCard, LIVE_HEATMAP } from './fixtures.js';

// Full user flow: dashboard -> 🔥 "Mapa Táctico" on a prop card -> tactical modal.
// The backend is faked in fixtures.js (offline by default, so the dashboard shows its mock props).

const modal = (page) => page.getByRole('dialog', { name: 'Mapa Táctico del Campo NFL' });
const tabNames = (page) => modal(page).getByRole('tab').allTextContents();

async function openTacticalMap(page, playerName) {
  await page.goto('/');
  await propCard(page, playerName).getByRole('button', { name: 'Mapa Táctico' }).click();
  await expect(modal(page)).toBeVisible();
}

// A plain hover() points at the center of the route's bounding box, which for a bent route
// (dig, out, post...) is empty field. A user hovers the line itself, so we aim at a point near
// the end of the route, where it is already apart from the other routes.
async function hoverRoute(page, dialog, routeType) {
  const route = dialog.getByTestId(`route-${routeType}`);
  await route.scrollIntoViewIfNeeded();
  // Page coordinates of that point: SVG units -> screen pixels with the line's own transform
  const { x, y } = await route.locator('polyline').first().evaluate((line) => {
    const point = line.getPointAtLength(line.getTotalLength() * 0.9);
    const ctm = line.getScreenCTM();
    return { x: point.x * ctm.a + point.y * ctm.c + ctm.e, y: point.x * ctm.b + point.y * ctm.d + ctm.f };
  });
  await page.mouse.move(x, y);
}

test.describe('Tactical modal', () => {
  test('opens the rushing heatmap of a RB from its prop card', async ({ page }) => {
    await openTacticalMap(page, 'Isiah Pacheco');

    const dialog = modal(page);
    await expect(dialog.getByTestId('position-scope-badge')).toHaveText('RB: Rushing + Passing Maps');
    await expect(dialog.getByLabel('Atleta Seleccionado')).toHaveValue('3');
    expect(await tabNames(page)).toEqual(['Rushing Heatmap', 'Route Tree', 'Man vs Zone']);
    await expect(dialog.getByRole('tab', { name: 'Rushing Heatmap' })).toHaveAttribute('aria-selected', 'true');

    // 5 run lanes drawn on the field, with the mock data because the backend is offline
    await expect(dialog.getByRole('button', { name: /^(Left|Right) (End|Tackle):|^Middle:/ })).toHaveCount(5);
    await expect(dialog.getByText('Mock data · backend offline')).toBeVisible();
  });

  test('shows the breakdown of a lane on hover and recolors it by metric', async ({ page }) => {
    await openTacticalMap(page, 'Isiah Pacheco');
    const dialog = modal(page);

    await dialog.getByRole('button', { name: 'Middle: Attempts 68' }).hover();
    await expect(dialog.getByText('Middle', { exact: true }).first()).toBeVisible();
    await expect(dialog.getByRole('definition').filter({ hasText: '310' })).toBeVisible();

    await dialog.getByRole('button', { name: 'Yards', exact: true }).click();
    await expect(dialog.getByRole('button', { name: 'Middle: Yards 310' })).toBeVisible();
  });

  test('uses live data when the tactics API answers', async ({ page, backend }) => {
    backend.tactics('heatmap.php', LIVE_HEATMAP);

    await openTacticalMap(page, 'Isiah Pacheco');
    const dialog = modal(page);

    await expect(dialog.getByText('Live data · /api/tactics')).toBeVisible();
    await expect(dialog.getByRole('button', { name: 'Middle: Attempts 99' })).toBeVisible();
  });

  test('shows an empty state instead of mock numbers when the API has no data for the athlete', async ({ page, backend }) => {
    backend.tactics('heatmap.php', { status: 'error', message: 'No tactical heatmap data found.' }, 404);

    await openTacticalMap(page, 'Isiah Pacheco');

    await expect(modal(page).getByText('No tactical data for this player.')).toBeVisible();
  });

  test('gives a TE the route tree, receiving zones and coverage tabs', async ({ page }) => {
    await openTacticalMap(page, 'Travis Kelce');
    const dialog = modal(page);

    await expect(dialog.getByTestId('position-scope-badge')).toHaveText('TE: Route Tree & Receiving Zones');
    expect(await tabNames(page)).toEqual(['Route Tree', 'Receiving Zones', 'Man vs Zone']);

    // Route tree: one interactive line per route, hover dims the rest
    await dialog.getByRole('tab', { name: 'Route Tree' }).click();
    await expect(dialog.locator('[data-testid^="route-"]')).toHaveCount(9);
    await hoverRoute(page, dialog, 'dig');
    await expect(dialog.getByTestId('route-go')).toHaveAttribute('opacity', '0.25');
    await expect(dialog.getByRole('definition').filter({ hasText: '158' })).toBeVisible();

    // Man vs Zone: both schemes, with the best one highlighted
    await dialog.getByRole('tab', { name: 'Man vs Zone' }).click();
    await expect(dialog.getByTestId('coverage-man')).toContainText('74.3%');
    await expect(dialog.getByTestId('coverage-zone')).toContainText('Best matchup');
  });

  test('updates the tabs when switching from a RB to a TE inside the modal', async ({ page }) => {
    await openTacticalMap(page, 'Isiah Pacheco');
    const dialog = modal(page);

    await dialog.getByLabel('Atleta Seleccionado').selectOption({ label: 'Travis Kelce (KC · TE)' });

    expect(await tabNames(page)).toEqual(['Route Tree', 'Receiving Zones', 'Man vs Zone']);
    await expect(dialog.getByTestId('position-scope-badge')).toHaveText('TE: Route Tree & Receiving Zones');
  });

  test('only offers the rushing map to a QB', async ({ page }) => {
    await openTacticalMap(page, 'Patrick Mahomes');

    expect(await tabNames(page)).toEqual(['Rushing Heatmap']);
    await expect(modal(page).getByTestId('position-scope-badge')).toHaveText('QB: Designed Runs Map');
  });

  test('closes with the close button', async ({ page }) => {
    await openTacticalMap(page, 'Isiah Pacheco');

    await modal(page).getByRole('button', { name: 'Cerrar mapa táctico' }).click();

    await expect(modal(page)).toBeHidden();
  });

  test('does not offer the tactical map on Liga MX props', async ({ page }) => {
    await page.goto('/');
    await expect(propCard(page, 'Isiah Pacheco')).toBeVisible();

    const ligaMxCards = page.locator('div.group').filter({ hasText: 'Liga MX' });
    for (const card of await ligaMxCards.all()) {
      await expect(card.getByRole('button', { name: 'Mapa Táctico' })).toHaveCount(0);
    }
  });
});
