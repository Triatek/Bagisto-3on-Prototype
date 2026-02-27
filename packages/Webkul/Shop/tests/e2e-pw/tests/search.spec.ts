import { test, expect } from "../setup";

test("should search by query", async ({ page }) => {
    await page.goto("");

    await page.getByLabel("Search Products").click();
    await page.getByLabel("Search Products").fill("arct");
    await page.getByLabel("Search Products").press("Enter");

    await expect(
        page.getByText("These are results for : arct").first()
    ).toBeVisible();
});
