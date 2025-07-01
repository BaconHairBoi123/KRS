"use client"

import {
  BookOpen,
  Calendar,
  FileText,
  GraduationCap,
  Home,
  User,
  Clock,
  HelpCircle,
  Settings,
  LogOut,
  ChevronDown,
} from "lucide-react"
import { useState } from "react"

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/ui/sidebar"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible"

const menuItems = [
  {
    title: "Dashboard",
    url: "/",
    icon: Home,
  },
  {
    title: "Pengisian KRS",
    url: "/krs",
    icon: BookOpen,
  },
  {
    title: "Jadwal Kuliah",
    url: "/jadwal",
    icon: Calendar,
  },
  {
    title: "Transkrip Nilai",
    url: "/transkrip",
    icon: FileText,
  },
  {
    title: "Riwayat KRS",
    url: "/riwayat",
    icon: Clock,
  },
]

const academicItems = [
  {
    title: "Profil Mahasiswa",
    url: "/profil",
    icon: User,
  },
  {
    title: "Panduan Akademik",
    url: "/panduan",
    icon: HelpCircle,
  },
]

const settingsItems = [
  {
    title: "Pengaturan Akun",
    url: "/pengaturan/akun",
    description: "Ubah password dan informasi akun",
  },
  {
    title: "Preferensi",
    url: "/pengaturan/preferensi",
    description: "Atur tema dan notifikasi",
  },
  {
    title: "Keamanan",
    url: "/pengaturan/keamanan",
    description: "Pengaturan keamanan akun",
  },
]

export function AppSidebar() {
  const [isSettingsOpen, setIsSettingsOpen] = useState(false)

  return (
    <Sidebar className="border-r">
      <SidebarHeader className="p-4">
        <div className="flex items-center gap-3">
          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white">
            <GraduationCap className="h-6 w-6" />
          </div>
          <div>
            <h2 className="text-lg font-semibold">Sistem KRS</h2>
            <p className="text-sm text-gray-500">Universitas Indonesia</p>
          </div>
        </div>
      </SidebarHeader>

      <SidebarContent className="flex-1 overflow-y-auto">
        <SidebarGroup>
          <SidebarGroupLabel>Menu Utama</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {menuItems.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton asChild>
                    <a href={item.url} className="flex items-center gap-3">
                      <item.icon className="h-4 w-4" />
                      <span>{item.title}</span>
                    </a>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        <SidebarGroup>
          <SidebarGroupLabel>Akademik</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              {academicItems.map((item) => (
                <SidebarMenuItem key={item.title}>
                  <SidebarMenuButton asChild>
                    <a href={item.url} className="flex items-center gap-3">
                      <item.icon className="h-4 w-4" />
                      <span>{item.title}</span>
                    </a>
                  </SidebarMenuButton>
                </SidebarMenuItem>
              ))}
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>

        {/* Settings Section dengan Collapsible */}
        <SidebarGroup>
          <SidebarGroupLabel>Sistem</SidebarGroupLabel>
          <SidebarGroupContent>
            <SidebarMenu>
              <SidebarMenuItem>
                <Collapsible open={isSettingsOpen} onOpenChange={setIsSettingsOpen}>
                  <CollapsibleTrigger asChild>
                    <SidebarMenuButton className="w-full justify-between">
                      <div className="flex items-center gap-3">
                        <Settings className="h-4 w-4" />
                        <span>Pengaturan</span>
                      </div>
                      <ChevronDown className={`h-4 w-4 transition-transform ${isSettingsOpen ? "rotate-180" : ""}`} />
                    </SidebarMenuButton>
                  </CollapsibleTrigger>
                  <CollapsibleContent className="ml-4 mt-1 space-y-1">
                    {settingsItems.map((item) => (
                      <SidebarMenuButton key={item.title} asChild size="sm">
                        <a href={item.url} className="flex flex-col items-start gap-1 pl-6">
                          <span className="text-sm">{item.title}</span>
                          <span className="text-xs text-gray-500">{item.description}</span>
                        </a>
                      </SidebarMenuButton>
                    ))}
                  </CollapsibleContent>
                </Collapsible>
              </SidebarMenuItem>
            </SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>

      {/* Footer dengan User Menu */}
      <SidebarFooter className="p-4 border-t">
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="w-full justify-start h-auto p-3">
              <div className="flex items-center gap-3 w-full">
                <Avatar className="h-8 w-8">
                  <AvatarImage src="/placeholder.svg?height=32&width=32" />
                  <AvatarFallback>JD</AvatarFallback>
                </Avatar>
                <div className="flex-1 min-w-0 text-left">
                  <p className="text-sm font-medium truncate">John Doe</p>
                  <p className="text-xs text-gray-500">NIM: 2021001234</p>
                </div>
                <ChevronDown className="h-4 w-4 text-gray-400" />
              </div>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" side="top" className="w-56 mb-2" sideOffset={8}>
            <DropdownMenuItem asChild>
              <a href="/profil" className="flex items-center gap-2">
                <User className="h-4 w-4" />
                <span>Profil Saya</span>
              </a>
            </DropdownMenuItem>
            <DropdownMenuItem asChild>
              <a href="/pengaturan" className="flex items-center gap-2">
                <Settings className="h-4 w-4" />
                <span>Pengaturan</span>
              </a>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
              <a href="/logout" className="flex items-center gap-2 text-red-600">
                <LogOut className="h-4 w-4" />
                <span>Keluar</span>
              </a>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarFooter>
    </Sidebar>
  )
}
