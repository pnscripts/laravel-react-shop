import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import AppLogo from '@/components/app-logo';
import { Icon } from '@/components/icon';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutDashboard, LogIn, Menu, ShoppingBag, Store } from 'lucide-react';

export function StorefrontHeader() {
    const { auth, cartCount } = usePage<SharedData>().props;

    return (
        <header className="border-sidebar-border/80 bg-background/95 sticky top-0 z-40 border-b backdrop-blur">
            <div className="mx-auto flex h-16 items-center gap-4 px-4 md:max-w-7xl">
                <div className="lg:hidden">
                    <Sheet>
                        <SheetTrigger asChild>
                            <Button variant="ghost" size="icon" className="h-[34px] w-[34px]">
                                <Menu className="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" className="bg-sidebar w-64">
                            <SheetTitle className="sr-only">Navigation Menu</SheetTitle>
                            <SheetHeader className="text-left">
                                <AppLogo />
                            </SheetHeader>
                            <div className="flex flex-col gap-3 p-4 text-sm font-medium">
                                <Link href={route('shop.index')} className="flex items-center gap-2">
                                    <Store className="h-4 w-4" />
                                    Shop
                                </Link>
                                <Link href={route('cart.index')} className="flex items-center gap-2">
                                    <ShoppingBag className="h-4 w-4" />
                                    Cart ({cartCount})
                                </Link>
                                {auth.user ? (
                                    <>
                                        <Link href={route('dashboard')} className="flex items-center gap-2">
                                            <LayoutDashboard className="h-4 w-4" />
                                            Dashboard
                                        </Link>
                                        {auth.user.is_admin && (
                                            <Link href={route('admin.products.index')} className="flex items-center gap-2">
                                                Admin
                                            </Link>
                                        )}
                                    </>
                                ) : (
                                    <>
                                        <Link href={route('login')} className="flex items-center gap-2">
                                            <LogIn className="h-4 w-4" />
                                            Log in
                                        </Link>
                                        <Link href={route('register')}>Register</Link>
                                    </>
                                )}
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link href={route('home')} className="flex items-center space-x-2">
                    <AppLogo />
                </Link>

                <nav className="ml-6 hidden items-center gap-1 lg:flex">
                    <Button variant="ghost" asChild>
                        <Link href={route('shop.index')}>
                            <Icon iconNode={Store} className="h-4 w-4" />
                            Shop
                        </Link>
                    </Button>
                </nav>

                <div className="ml-auto flex items-center gap-2">
                    <Button variant="ghost" asChild>
                        <Link href={route('cart.index')} className="relative">
                            <ShoppingBag className="h-5 w-5" />
                            <span className="hidden sm:inline">Cart</span>
                            {cartCount > 0 && (
                                <span className="bg-primary text-primary-foreground absolute -top-1 -right-1 flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-xs">
                                    {cartCount}
                                </span>
                            )}
                        </Link>
                    </Button>
                    <AppearanceToggleDropdown />
                    {auth.user ? (
                        <Button variant="outline" asChild>
                            <Link href={route('dashboard')}>Dashboard</Link>
                        </Button>
                    ) : (
                        <>
                            <Button variant="ghost" asChild>
                                <Link href={route('login')}>Log in</Link>
                            </Button>
                            <Button asChild>
                                <Link href={route('register')}>Register</Link>
                            </Button>
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}
