from pathlib import Path
import xml.etree.ElementTree as ET


REPOSITORY = "https://github.com/wx2020/unraid-easytier"
PLUGIN_URL = f"{REPOSITORY}/releases/latest/download/easytier.plg"
SUPPORT_URL = f"{REPOSITORY}/issues"


def required_text(root: ET.Element, tag: str) -> str:
    element = root.find(tag)
    value = "" if element is None or element.text is None else element.text.strip()
    if not value:
        raise ValueError(f"Missing required <{tag}> value")
    return value


profile = ET.parse(Path("ca_profile.xml")).getroot()
if profile.tag != "CommunityApplications":
    raise ValueError("ca_profile.xml must use <CommunityApplications> as its root")
required_text(profile, "Profile")
required_text(profile, "Icon")
if required_text(profile, "WebPage") != REPOSITORY:
    raise ValueError("ca_profile.xml has an unexpected <WebPage>")
if required_text(profile, "Forum") != SUPPORT_URL:
    raise ValueError("ca_profile.xml has an unexpected <Forum>")

plugin = ET.parse(Path("templates/easytier.xml")).getroot()
if plugin.tag != "Plugin":
    raise ValueError("Plugin template must use <Plugin> as its root")
for field in ("Name", "Overview", "Category", "Icon"):
    required_text(plugin, field)
# P2 T-03: Overview must be meaningful (>20 chars)
if len(required_text(plugin, "Overview").strip()) <= 20:
    raise ValueError("Plugin Overview too short, must be >20 chars")
if required_text(plugin, "PluginURL") != PLUGIN_URL:
    raise ValueError("Plugin template must install the latest release PLG")
if required_text(plugin, "Project") != REPOSITORY:
    raise ValueError("Plugin template has an unexpected <Project>")
if required_text(plugin, "Support") != SUPPORT_URL:
    raise ValueError("Plugin template has an unexpected <Support>")

print("Community Applications XML validation passed.")
